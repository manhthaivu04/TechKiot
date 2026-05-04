<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case EXPIRED   = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Chờ kích hoạt',
            self::ACTIVE    => 'Đang hoạt động',
            self::EXPIRED   => 'Hết hạn',
            self::CANCELLED => 'Đã hủy',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
