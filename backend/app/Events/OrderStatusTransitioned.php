<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusTransitioned — Event phát ra sau khi đơn hàng chuyển trạng thái.
 *
 * ── Observer Pattern ─────────────────────────────────────────────────────────
 * Dùng để decoupling các side-effects của việc chuyển trạng thái.
 *
 * Các Listener:
 *   ┌─────────────────────────────────────────────────────────────────┐
 *   │ [BASIC]   NotifyKitchenStatusListener → Báo màn hình KDS của bếp
 *   │ [PRO]     NotifyTableStatusListener   → Cập nhật trạng thái bàn
 *   │ [PREMIUM] TriggerInventoryAdjust      → Điều chỉnh kho khi cancel
 *   └─────────────────────────────────────────────────────────────────┘
 */
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;

class OrderStatusTransitioned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order       $order,
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("order.{$this->order->id}"),
            new Channel("restaurant.{$this->order->restaurant_id}.kitchen")
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusTransitioned';
    }
}
