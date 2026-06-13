<?php

namespace App\Services\Orders\Actions;

use App\Events\OrderItemRemoved;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class RemoveOrderItemAction
{
    /**
     * @param Order $order
     * @param string $itemId ID của bảng order_items (KHÔNG phải item_id của bảng items)
     * @return Order
     */
    public function execute(Order $order, string $itemId): Order
    {
        // 1. Kiểm tra trạng thái đơn hàng
        if (! $order->state()->canUpdateItems()) {
            throw new \DomainException("Không thể hủy món khi đơn hàng đã phục vụ hoặc thanh toán.");
        }

        $result = DB::transaction(function () use ($order, $itemId) {
            $orderItem = $order->items()->findOrFail($itemId);

            // 2. Tính lại tổng tiền
            $order->subtotal_amount -= $orderItem->line_total;
            $order->final_amount = $order->subtotal_amount + $order->tax_amount - $order->discount_amount;
            $order->save();

            // 3. Clone lại data của OrderItem trước khi xóa để gửi Event
            $removedItemData = $orderItem->toArray();

            // 4. Xóa dòng món ăn
            $orderItem->delete();

            return [
                'order' => $order->refresh()->load('items.item'),
                'data'  => $removedItemData
            ];
        });

        // 5. Bắn event báo cáo (vd: báo Bếp ngưng nấu) SAU KHI transaction commit
        OrderItemRemoved::dispatch($result['order'], $result['data']);

        return $result['order'];
    }
}
