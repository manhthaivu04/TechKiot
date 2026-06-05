<?php

namespace App\States\Order;

use App\Enums\OrderStatus;

class OpenState extends OrderState
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, [
            OrderStatus::Cooking,
            OrderStatus::Cancelled,
        ], true);
    }

    public function label(): string
    {
        return 'Đang mở';
    }

    public function getValue(): OrderStatus
    {
        return OrderStatus::Open;
    }

    public function isEditable(): bool
    {
        return true;
    }
}
