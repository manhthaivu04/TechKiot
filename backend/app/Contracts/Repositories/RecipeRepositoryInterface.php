<?php

namespace App\Contracts\Repositories;

use App\Models\Item;

/**
 * Contract cho Recipe Repository — quản lý công thức (BOM).
 *
 * Tách riêng khỏi ItemRepositoryInterface vì:
 *   - SRP: Item repo lo CRUD item, Recipe repo lo sync công thức.
 *   - Dễ mock/test — có thể stub syncIngredients() mà không ảnh hưởng Item CRUD.
 */
interface RecipeRepositoryInterface
{
    /**
     * Đồng bộ danh sách nguyên liệu cho 1 Món ăn (MENU_ITEM).
     *
     * Sử dụng Eloquent sync() nên:
     *   - Nguyên liệu mới → INSERT vào pivot.
     *   - Nguyên liệu có sẵn nhưng thay đổi quantity → UPDATE pivot.
     *   - Nguyên liệu bị xóa khỏi danh sách → DELETE khỏi pivot.
     *
     * @param  string $productId      UUID của Món ăn.
     * @param  array<int, array{ingredient_id: string, quantity: float}> $ingredientsData
     * @return Item   Model đã được refresh kèm relation ingredients.
     */
    public function syncIngredients(string $productId, array $ingredientsData): Item;
}
