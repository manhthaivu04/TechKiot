<?php

namespace App\Services;

use App\Contracts\Services\OrderServiceInterface;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderStrategyResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OrderService — Core business logic cho module đơn hàng.
 *
 * Nguyên tắc thiết kế:
 * - Single Responsibility: chỉ xử lý workflow tạo đơn và thanh toán.
 * - Open/Closed: thêm kênh mới qua Strategy, không sửa class này.
 * - Dependency Inversion: inject OrderStrategyResolver thay vì new cứng.
 *
 * Tất cả thao tác ghi DB trong placeOrder() đều nằm trong một transaction
 * để đảm bảo tính atomicity — nếu bất kỳ bước nào lỗi, toàn bộ rollback.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderStrategyResolver $strategyResolver,
    ) {}

    // ─── Place Order ──────────────────────────────────────────────────────────

    /**
     * Tạo đơn hàng mới — luồng chính của module Basic.
     *
     * Workflow:
     *   1. Sinh mã đơn hàng duy nhất
     *   2. Tạo record Order (amounts = 0, cập nhật sau khi có items)
     *   3. Batch query tất cả Items để lấy sale_price chuẩn (tránh N+1)
     *   4. Tạo OrderItems với unit_price = snapshot của sale_price
     *   5. Tính subtotal, tax, discount → cập nhật Order
     *   6. Gọi Strategy tương ứng source_channel để xử lý side-effects
     *
     * @throws \DomainException         Nếu item không thuộc restaurant
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Nếu item không tồn tại
     */
    public function placeOrder(PlaceOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {

            // ── Step 1: Tạo Order với giá trị mặc định ───────────────────────
            $order = Order::create([
                'restaurant_id'      => $dto->restaurantId,
                'table_id'           => $dto->tableId,
                'order_code'         => $this->generateOrderCode($dto->restaurantId),
                'source_channel'     => $dto->sourceChannel,
                'service_type'       => $dto->tableId ? 'dine_in' : 'takeaway',
                'status'             => OrderStatus::Open,
                'customer_name'      => $dto->customerName,
                'customer_phone'     => $dto->customerPhone,
                'customer_reference' => $dto->customerReference,
                'guest_count'        => $dto->guestCount,
                'note'               => $dto->note,
                'created_by'         => $dto->createdBy,
                // amounts sẽ được cập nhật sau khi tính toán xong
                'subtotal_amount'    => 0,
                'discount_amount'    => $dto->discountAmount,
                'tax_amount'         => 0,
                'final_amount'       => 0,
            ]);

            // ── Step 2: Batch query Items — tránh N+1 query ───────────────────
            // Lý do dùng whereIn thay vì loop: 1 query duy nhất dù có 100 món.
            $itemIds  = array_column($dto->items, 'itemId');
            $itemsMap = Item::query()
                ->whereIn('id', $itemIds)
                ->where('restaurant_id', $dto->restaurantId)   // tenant isolation
                ->where('is_active', true)
                ->get()
                ->keyBy('id'); // Collection key'd by UUID để lookup O(1)

            // ── Step 3: Validate tất cả items trước khi insert ────────────────
            // Validate trước khi insert để đảm bảo fail fast, không insert partial.
            foreach ($dto->items as $itemDTO) {
                if (! $itemsMap->has($itemDTO->itemId)) {
                    throw new \DomainException(
                        "Item [{$itemDTO->itemId}] không tồn tại hoặc không thuộc nhà hàng này."
                    );
                }
            }

            // ── Step 4: Tạo OrderItems với giá snapshot ───────────────────────
            $orderItemsData = [];
            $subtotal = 0.0;

            foreach ($dto->items as $itemDTO) {
                /** @var Item $item */
                $item      = $itemsMap->get($itemDTO->itemId);
                $unitPrice = (float) $item->sale_price; // snapshot — không dùng lại sau
                $lineTotal = $unitPrice * $itemDTO->quantity;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'id'         => Str::uuid()->toString(),
                    'order_id'   => $order->id,
                    'item_id'    => $item->id,
                    'quantity'   => $itemDTO->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'note'       => $itemDTO->note,
                    'status'     => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Dùng insert() thay vì vòng lặp create() — 1 query thay vì N queries
            \App\Models\OrderItem::insert($orderItemsData);

            // ── Step 5: Tính toán và cập nhật tổng tiền ───────────────────────
            $taxAmount   = $subtotal * $dto->taxRate;
            $finalAmount = $subtotal + $taxAmount - $dto->discountAmount;

            $order->update([
                'subtotal_amount' => $subtotal,
                'tax_amount'      => $taxAmount,
                'final_amount'    => max(0, $finalAmount), // Không cho phép âm
            ]);

            // ── Step 6: Gọi Strategy của kênh tạo đơn ────────────────────────
            // OrderService không biết và không cần biết strategy làm gì cụ thể.
            // Đây là điểm mở rộng chính của architecture.
            $strategy = $this->strategyResolver->resolve($dto->sourceChannel);
            $strategy->handle($order, $dto);

            // Load relationships để caller không cần query lại
            return $order->load(['items.item', 'payments']);
        });
    }

    // ─── Record Payment ───────────────────────────────────────────────────────

    /**
     * Ghi nhận thanh toán và tự động cập nhật trạng thái đơn nếu đủ tiền.
     *
     * Workflow:
     *   1. Tạo Payment record
     *   2. Kiểm tra tổng đã thanh toán
     *   3. Nếu đủ → chuyển Order sang Paid
     *
     * @param Order  $order         Đơn hàng cần thanh toán
     * @param float  $amount        Số tiền thanh toán lần này
     * @param string $method        Phương thức (cash, card, transfer, ewallet)
     * @param string $createdBy     ID nhân viên thực hiện
     * @param string|null $referenceNo Mã giao dịch ngân hàng/ví
     */
    public function recordPayment(
        Order   $order,
        float   $amount,
        string  $method,
        string  $createdBy,
        ?string $referenceNo = null,
    ): Payment {
        return DB::transaction(function () use ($order, $amount, $method, $createdBy, $referenceNo): Payment {

            $payment = Payment::create([
                'order_id'       => $order->id,
                'amount'         => $amount,
                'payment_method' => $method,
                'reference_no'   => $referenceNo,
                'paid_at'        => now(),
                'created_by'     => $createdBy,
            ]);

            // Tự động chuyển sang Paid nếu đã thanh toán đủ
            // isPaidInFull() tổng hợp từ tất cả payments (hỗ trợ split payment)
            $order->refresh(); // Reload để sum() tính đúng payment vừa insert
            if ($order->isPaidInFull()) {
                $order->transitionTo(OrderStatus::Paid);
            }

            return $payment;
        });
    }

    // ─── Transition Status ────────────────────────────────────────────────────

    /**
     * Chuyển trạng thái đơn hàng — delegate xuống Model để giữ business rule tập trung.
     *
     * @throws \DomainException Nếu transition không hợp lệ
     */
    public function transition(Order $order, OrderStatus $newStatus): Order
    {
        $order->transitionTo($newStatus);
        return $order->refresh();
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Sinh mã đơn hàng theo format: KT-YYYYMMDD-XXXX
     * e.g., KT-20240510-0042
     *
     * Dùng DB count trong ngày để đảm bảo sequence tăng dần,
     * không cần Redis hay sequence generator.
     */
    private function generateOrderCode(string $restaurantId): string
    {
        $today = now()->format('Ymd');

        $todayCount = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $sequence = str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);

        return "KT-{$today}-{$sequence}";
    }
}
