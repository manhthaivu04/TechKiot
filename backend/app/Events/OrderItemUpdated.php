<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;

class OrderItemUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Order $order
     * @param OrderItem $orderItem Dữ liệu của OrderItem vừa được cập nhật
     * @param int|null $oldQuantity Số lượng cũ trước khi cập nhật (để xử lý bù trừ tồn kho)
     */
    public function __construct(
        public Order $order,
        public OrderItem $orderItem,
        public readonly ?int $oldQuantity = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->order->restaurant_id}.kitchen");
    }

    public function broadcastAs(): string
    {
        return 'OrderItemUpdated';
    }
}
