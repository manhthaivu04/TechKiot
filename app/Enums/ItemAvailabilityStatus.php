<?php

namespace App\Enums;

enum ItemAvailabilityStatus: string
{
    case IN_STOCK = 'IN_STOCK';
    case OUT_OF_STOCK = 'OUT_OF_STOCK';
    case SUSPENDED = 'SUSPENDED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
