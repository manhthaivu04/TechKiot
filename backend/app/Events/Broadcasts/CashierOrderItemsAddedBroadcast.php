<?php

namespace App\Events\Broadcasts;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashierOrderItemsAddedBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public array $newItems,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->order->restaurant_id}.cashier");
    }

    public function broadcastAs(): string
    {
        return 'OrderItemsAdded';
    }
}
