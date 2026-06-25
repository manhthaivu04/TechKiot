<?php

namespace App\Events\Broadcasts;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashierNewQrOrderBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function broadcastOn(): Channel
    {
        // Kênh dành riêng cho Thu Ngân (Cashier)
        return new Channel("restaurant.{$this->order->restaurant_id}.cashier");
    }

    public function broadcastAs(): string
    {
        return 'NewQrOrder';
    }
}
