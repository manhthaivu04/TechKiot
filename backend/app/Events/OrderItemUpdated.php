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

    public function broadcastOn(): array
    {
        $channels = [
            new Channel("restaurant.{$this->order->restaurant_id}.kitchen"),
        ];

        // Chỉ thông báo cho Cashier khi bếp báo xong (Ready) hoặc đã lên món (Served)
        if (
            $this->orderItem->status === \App\Enums\OrderItemStatus::Ready ||
            $this->orderItem->status === \App\Enums\OrderItemStatus::Served
        ) {
            $channels[] = new Channel("restaurant.{$this->order->restaurant_id}.cashier");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'OrderItemUpdated';
    }
}
