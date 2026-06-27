<?php

namespace App\Services\Orders\Actions;

use App\Events\OrderItemUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class UpdateOrderItemAction
{
    /**
     * @param Order $order
     * @param string $itemId ID của bảng order_items
     * @param array{quantity?: int, note?: string} $data
     * @return Order
     */
    public function execute(Order $order, string $itemId, array $data): Order
    {
        // 1. Kiểm tra trạng thái đơn hàng
        if (! $order->state()->canUpdateItems()) {
            throw new \DomainException("Không thể cập nhật món khi đơn hàng đã phục vụ hoặc thanh toán.");
        }

        $result = DB::transaction(function () use ($order, $itemId, $data) {
            $orderItem = $order->items()->findOrFail($itemId);
            
            $oldQuantity = clone $orderItem;
            $oldQuantityVal = $oldQuantity->quantity;

            // Nếu update quantity, cần tính lại tiền
            if (isset($data['quantity']) && $data['quantity'] !== $orderItem->quantity) {
                $oldLineTotal = $orderItem->line_total;
                $newLineTotal = $orderItem->unit_price * $data['quantity'];
                
                // Trừ tiền cũ, cộng tiền mới
                $order->subtotal_amount = $order->subtotal_amount - $oldLineTotal + $newLineTotal;
                $order->final_amount = $order->subtotal_amount + $order->tax_amount - $order->discount_amount;
                $order->save();

                $orderItem->quantity = $data['quantity'];
                $orderItem->line_total = $newLineTotal;
            }

            // Nếu update note
            if (array_key_exists('note', $data)) {
                $orderItem->note = $data['note'];
            }

            // Nếu update status (Ví dụ: Bếp báo nấu xong -> ready, Thu ngân báo bưng ra -> served)
            if (array_key_exists('status', $data)) {
                $orderItem->status = $data['status'];
            }

            $orderItem->save();

            return [
                'order' => $order->refresh()->load('items.item'),
                'item'  => $orderItem,
                'oldQuantity' => $oldQuantityVal
            ];
        });

        // Bắn event báo cáo sự thay đổi SAU KHI transaction đã commit
        OrderItemUpdated::dispatch($result['order'], $result['item'], $result['oldQuantity']);

        return $result['order'];
    }
}
