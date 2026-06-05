<?php

namespace App\Listeners;

use App\Events\OrderStatusTransitioned;
use Illuminate\Support\Facades\Log;

/**
 * NotifyKitchenStatusListener [BASIC]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Broadcast cập nhật trạng thái đơn hàng xuống màn hình KDS của bếp.
 * VD: Thu ngân xác nhận Served → bếp biết có thể lấy đơn tiếp.
 *     Đơn bị Cancel → bếp dừng nấu.
 *
 * ── TODO ─────────────────────────────────────────────────────────────────────
 * Uncomment khi implement WebSocket channel.
 */
class NotifyKitchenStatusListener
{
    public function handle(OrderStatusTransitioned $event): void
    {
        Log::info("[KDS] Đơn [{$event->order->order_code}] chuyển: {$event->from->value} → {$event->to->value}");

        // TODO [BASIC]: Broadcast trạng thái mới xuống màn KDS
        // broadcast(new \App\Events\KitchenOrderStatusEvent($event->order, $event->to))->toOthers();
    }
}
