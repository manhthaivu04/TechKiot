<?php

namespace App\Services\Orders\Actions;

use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Item;
use App\Models\Order;
use App\Services\Orders\OrderStrategyResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PlaceOrderAction — Tạo đơn hàng mới.
 *
 * ── Single Responsibility ────────────────────────────────────────────────────
 * Class này chỉ làm DUY NHẤT một việc: nhận PlaceOrderDTO, tạo Order
 * và các OrderItems trong một Transaction, sau đó delegate side-effects
 * cho OrderSourceStrategy tương ứng.
 *
 * Bằng cách tách ra Action riêng:
 * - OrderService không còn "phình to" khi thêm AddItemsAction, CancelItemAction...
 * - Class này dễ test độc lập (unit test PlaceOrderAction, mock strategyResolver).
 * - OCP: thêm kênh đặt hàng mới → chỉ sửa OrderStrategyResolver, không đụng file này.
 */
class PlaceOrderAction
{
    public function __construct(
        private readonly OrderStrategyResolver $strategyResolver,
    ) {}

    /**
     * Thực thi việc tạo đơn hàng mới.
     *
     * Workflow (toàn bộ trong 1 DB Transaction — atomicity):
     *   1. Tạo Order record với giá trị mặc định
     *   2. Batch query Items để lấy giá (tránh N+1)
     *   3. Validate tất cả items trước khi insert (fail fast)
     *   4. Tạo OrderItems với unit_price = snapshot của sale_price
     *   5. Tính subtotal, tax, discount → cập nhật Order
     *   6. Delegate side-effects cho Strategy của source_channel
     *
     * @throws \DomainException         Nếu item không thuộc restaurant hoặc không active
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Item không tồn tại
     */
    public function execute(PlaceOrderDTO $dto): Order
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
                'subtotal_amount'    => 0,
                'discount_amount'    => $dto->discountAmount,
                'tax_amount'         => 0,
                'final_amount'       => 0,
            ]);

            // ── Step 2: Batch query Items — tránh N+1 query ───────────────────
            $itemIds  = array_column($dto->items, 'itemId');
            $itemsMap = Item::query()
                ->whereIn('id', $itemIds)
                ->where('restaurant_id', $dto->restaurantId)   // tenant isolation
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // ── Step 3: Validate tất cả items trước khi insert ────────────────
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
                $unitPrice = (float) $item->sale_price;
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

            // 1 query thay vì N queries
            \App\Models\OrderItem::insert($orderItemsData);

            // ── Step 5: Tính toán và cập nhật tổng tiền ───────────────────────
            $taxAmount   = $subtotal * $dto->taxRate;
            $finalAmount = $subtotal + $taxAmount - $dto->discountAmount;

            $order->update([
                'subtotal_amount' => $subtotal,
                'tax_amount'      => $taxAmount,
                'final_amount'    => max(0, $finalAmount),
            ]);

            // ── Step 6: Delegate side-effects cho Strategy ────────────────────
            // Action này không biết và không cần biết Strategy làm gì cụ thể.
            // Thêm kênh mới (Zalo, MiniApp...) → chỉ sửa OrderStrategyResolver.
            $strategy = $this->strategyResolver->resolve($dto->sourceChannel);
            $strategy->handle($order, $dto);

            return $order->load(['items.item', 'payments']);
        });
    }

    /**
     * Sinh mã đơn hàng theo format: KT-YYYYMMDD-XXXX
     * e.g., KT-20240510-0042
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
