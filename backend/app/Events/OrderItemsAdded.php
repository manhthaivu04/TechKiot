<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;

class OrderItemsAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Order $order
     * @param list<\App\DTOs\PlaceOrderItemDTO> $newItems
     */
    public function __construct(
        public Order $order,
        public array $newItems,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->order->restaurant_id}.kitchen");
    }

    public function broadcastAs(): string
    {
        return 'OrderItemsAdded';
    }
}
