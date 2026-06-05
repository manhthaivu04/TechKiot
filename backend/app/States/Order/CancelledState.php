<?php

namespace App\States\Order;

use App\Enums\OrderStatus;

class CancelledState extends OrderState
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return false;
    }

    public function label(): string
    {
        return 'Đã huỷ';
    }

    public function getValue(): OrderStatus
    {
        return OrderStatus::Cancelled;
    }

    public function canAddItems(): bool
    {
        return false;
    }

    public function canUpdateItems(): bool
    {
        return false;
    }
}
