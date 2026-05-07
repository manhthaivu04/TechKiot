<?php

namespace App\Enums;

enum ItemType: string
{
    case MENU_ITEM = 'MENU_ITEM'; // Gói Basic
    case INGREDIENT = 'INGREDIENT'; // Dành cho mở rộng gói Premium sau này

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
