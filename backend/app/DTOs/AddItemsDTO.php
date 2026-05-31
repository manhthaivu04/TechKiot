<?php

namespace App\DTOs;

use App\Models\Order;

class AddItemsDTO
{
    /**
     * @param Order $order
     * @param list<PlaceOrderItemDTO> $newItems
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $newItems,
    ) {}
}
