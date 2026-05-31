<?php

namespace App\States\Order;

use App\Enums\OrderStatus;

class ServedState extends OrderState
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, [
            OrderStatus::Paid,
            OrderStatus::Cancelled,
        ], true);
    }

    public function label(): string
    {
        return 'Đã phục vụ';
    }

    public function getValue(): OrderStatus
    {
        return OrderStatus::Served;
    }
}
