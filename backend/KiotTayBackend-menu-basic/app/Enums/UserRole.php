<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case OWNER       = 'OWNER';
    case MANAGER     = 'MANAGER';
    case WAITER      = 'WAITER';
    case KITCHEN     = 'KITCHEN';
    case CASHIER     = 'CASHIER';

    /** Roles that belong to a restaurant tenant */
    public function isTenantRole(): bool
    {
        return $this !== self::SUPER_ADMIN;
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::OWNER       => 'Chủ quán',
            self::MANAGER     => 'Quản lý',
            self::WAITER      => 'Phục vụ',
            self::KITCHEN     => 'Bếp',
            self::CASHIER     => 'Thu ngân',
        };
    }
}
