<?php

namespace App\Services\Items;

use App\Contracts\Items\ItemCreatorInterface;
use App\Enums\ItemType;
use App\Services\Items\Creators\IngredientCreator;
use App\Services\Items\Creators\MenuItemCreator;
use InvalidArgumentException;

/**
 * ItemFactory — Khởi tạo đúng Creator dựa vào ItemType enum.
 *
 * ── Factory Pattern ────────────────────────────────────────────────────────────
 * Tập trung việc "chọn" Creator tại một nơi duy nhất.
 * Caller (ItemService) không cần biết class cụ thể nào được dùng.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Thêm loại item mới (COMBO, SEMI_PRODUCT)? → Thêm 1 case vào match() + 1 class Creator mới.
 * Không sửa bất kỳ class nào khác.
 *
 * Dùng app() helper để Laravel tự inject dependencies cho Creator qua IoC Container.
 */
class ItemFactory
{
    public static function make(string $itemType): ItemCreatorInterface
    {
        return match ($itemType) {
            ItemType::MENU_ITEM->value  => app(MenuItemCreator::class),
            ItemType::INGREDIENT->value => app(IngredientCreator::class),
            // ItemType::COMBO->value => app(ComboCreator::class),
            default => throw new InvalidArgumentException("Loại Item không hợp lệ: [{$itemType}]"),
        };
    }
}
