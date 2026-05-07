<?php

namespace App\Enums;

enum RestaurantStatus: string
{
    case ACTIVE    = 'active';
    case INACTIVE  = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE    => 'Hoạt động',
            self::INACTIVE  => 'Không hoạt động',
            self::SUSPENDED => 'Bị khóa',
        };
    }

    public function isAccessible(): bool
    {
        return $this === self::ACTIVE;
    }
}
