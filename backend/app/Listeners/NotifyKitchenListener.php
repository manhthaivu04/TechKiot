<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

/**
 * NotifyKitchenListener [BASIC]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Broadcast đơn hàng xuống màn hình Kitchen Display System (KDS) của bếp.
 * Bếp sẽ thấy đơn mới xuất hiện và biết cần chuẩn bị món gì.
 *
 * ── Gói áp dụng: BASIC ───────────────────────────────────────────────────────
 * Mọi gói đều cần thông báo cho bếp — đây là feature cơ bản nhất.
 *
 * ── TODO (khi implement WebSocket) ───────────────────────────────────────────
 * Uncomment dòng Broadcast bên dưới và tạo KitchenOrderEvent.
 * Không cần sửa file này — chỉ implement event + channel là xong.
 */
class NotifyKitchenListener
{
    public function handle(OrderPlaced|\App\Events\OrderItemsAdded $event): void
    {
        $order = $event->order;

        Log::info("[KDS] Đơn hàng mới [{$order->order_code}] tới bếp.", [
            'restaurant_id' => $order->restaurant_id,
            'items_count'   => $order->items->count(),
        ]);

        // TODO [BASIC]: Broadcast real-time xuống màn KDS
        // broadcast(new \App\Events\KitchenNewOrderEvent($order))->toOthers();
    }
}
