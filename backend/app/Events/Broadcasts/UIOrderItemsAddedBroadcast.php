<?php

namespace App\Events\Broadcasts;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UIOrderItemsAddedBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public array $newItems,
    ) {}

    public function broadcastOn(): Channel
    {
        // Chỉ phát sóng về điện thoại khách hàng
        return new Channel("order.{$this->order->id}");
    }

    /**
     * GIỮ NGUYÊN TÊN EVENT CŨ ĐỂ KHÔNG BREAK FRONTEND.
     */
    public function broadcastAs(): string
    {
        return 'OrderItemsAdded';
    }
}
