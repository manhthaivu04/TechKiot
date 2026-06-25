<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderItemsAdded
{
    use Dispatchable, SerializesModels;

    /**
     * @param Order $order
     * @param list<\App\DTOs\PlaceOrderItemDTO> $newItems
     */
    public function __construct(
        public Order $order,
        public array $newItems,
    ) {}
}
