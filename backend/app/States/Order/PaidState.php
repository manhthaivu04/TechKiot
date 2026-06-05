<?php

namespace App\States\Order;

use App\Enums\OrderStatus;

class PaidState extends OrderState
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return false;
    }

    public function label(): string
    {
        return 'Đã thanh toán';
    }

    public function getValue(): OrderStatus
    {
        return OrderStatus::Paid;
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
