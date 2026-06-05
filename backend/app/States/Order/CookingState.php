<?php

namespace App\States\Order;

use App\Enums\OrderStatus;

class CookingState extends OrderState
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, [
            OrderStatus::Served,
            OrderStatus::Cancelled,
        ], true);
    }

    public function label(): string
    {
        return 'Đang nấu';
    }

    public function getValue(): OrderStatus
    {
        return OrderStatus::Cooking;
    }
}
