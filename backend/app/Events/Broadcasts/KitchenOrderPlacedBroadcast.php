<?php

namespace App\Events\Broadcasts;

use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenOrderPlacedBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly PlaceOrderDTO $dto,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->order->restaurant_id}.kitchen");
    }

    /**
     * GIỮ NGUYÊN TÊN EVENT CŨ.
     * Nhờ dòng này, Frontend hoàn toàn KHÔNG biết Backend đã đổi class.
     * Mọi thứ trên màn hình FE vẫn chạy bình thường.
     */
    public function broadcastAs(): string
    {
        return 'OrderPlaced';
    }
}
