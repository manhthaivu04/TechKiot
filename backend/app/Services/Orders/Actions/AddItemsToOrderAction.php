<?php

namespace App\Services\Orders\Actions;

use App\DTOs\PlaceOrderItemDTO;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderStrategyResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AddItemsToOrderAction — Xử lý nghiệp vụ khách gọi thêm món.
 *
 * ── Single Responsibility ────────────────────────────────────────────────────
 * Chuyên trách việc: Nhận các món mới -> tính tiền -> thêm vào Order cũ
 * -> cập nhật tổng tiền -> báo rẽ nhánh (Strategy) cho bếp.
 * 
 * Class này hoàn toàn độc lập với PlaceOrderAction. Lỗi ở đây không làm
 * ảnh hưởng đến tiến trình tạo đơn mới.
 */
class AddItemsToOrderAction
{
    public function __construct(
        private readonly OrderStrategyResolver $strategyResolver,
    ) {}

    /**
     * @param Order $order Đơn hàng hiện tại
     * @param list<PlaceOrderItemDTO> $newItems Danh sách món gọi thêm
     * @return Order
     */
    public function execute(Order $order, array $newItems): Order
    {
        // 1. Kiểm tra trạng thái đơn: Chỉ đơn đang Open/Processing mới được thêm món
        if (in_array($order->status->value, ['paid', 'cancelled'])) {
            throw new \DomainException("Không thể thêm món vào đơn hàng đã thanh toán hoặc đã hủy.");
        }

        if (empty($newItems)) {
            return $order;
        }

        return DB::transaction(function () use ($order, $newItems): Order {

            // 2. Lấy thông tin giá hiện tại của các món mới
            $itemIds  = array_column($newItems, 'itemId');
            $itemsMap = Item::query()
                ->whereIn('id', $itemIds)
                ->where('restaurant_id', $order->restaurant_id)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // 3. Validate và chuẩn bị dữ liệu insert
            $orderItemsData = [];
            $additionalSubtotal = 0.0;

            foreach ($newItems as $itemDTO) {
                if (! $itemsMap->has($itemDTO->itemId)) {
                    throw new \DomainException("Item [{$itemDTO->itemId}] không tồn tại.");
                }

                /** @var Item $item */
                $item = $itemsMap->get($itemDTO->itemId);
                $unitPrice = (float) $item->sale_price;
                $lineTotal = $unitPrice * $itemDTO->quantity;
                $additionalSubtotal += $lineTotal;

                $orderItemsData[] = [
                    'id'         => Str::uuid()->toString(),
                    'order_id'   => $order->id,
                    'item_id'    => $item->id,
                    'quantity'   => $itemDTO->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'note'       => $itemDTO->note,
                    'status'     => 'pending', // Món mới mặc định là pending
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 4. Insert món mới vào bảng order_items
            OrderItem::insert($orderItemsData);

            // 5. Cập nhật lại tổng tiền bảng Order
            // (Chưa tính tax_rate vì ta lưu số tiền tĩnh, muốn chuẩn phải lưu thêm cột tax_rate ở Order)
            // Giả lập tính nhanh:
            $order->subtotal_amount += $additionalSubtotal;
            // Ở dự án thực tế, bạn sẽ lấy tax_rate của Order ra để tính lại tax_amount.
            $order->final_amount = $order->subtotal_amount + $order->tax_amount - $order->discount_amount;
            $order->save();

            // 6. Tái sử dụng Strategy cũ để bắn thông báo
            // Ví dụ: QrTableOrderStrategy sẽ nhận event này và bắn websocket "Bàn 5 gọi thêm bia!"
            // Ở đây dùng null DTO hoặc tạo UpdateOrderDTO, tạm truyền null để minh họa
            $strategy = $this->strategyResolver->resolve($order->source_channel);
            // $strategy->handleUpdate($order, $newItems); <-- Cần thêm hàm handleUpdate vào interface nếu cần

            return $order->refresh()->load('items.item');
        });
    }
}
