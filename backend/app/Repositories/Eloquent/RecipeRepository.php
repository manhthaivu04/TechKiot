<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RecipeRepositoryInterface;
use App\Events\RecipeUpdated;
use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Validation\ValidationException;

/**
 * Eloquent implementation cho RecipeRepositoryInterface.
 *
 * ── Luồng xử lý syncIngredients() ──────────────────────────────────────────
 * 1. Tìm Món ăn (MENU_ITEM) theo productId.
 * 2. Chuyển mảng ingredientsData → format mà Eloquent sync() hiểu:
 *      [ ingredient_id => ['quantity' => 0.200], ... ]
 * 3. Gọi sync() — Laravel tự INSERT/UPDATE/DELETE pivot rows.
 * 4. Dispatch RecipeUpdated event → Listener tự tính lại cost_price.
 * 5. Trả về model đã refresh kèm relation ingredients.
 */
class RecipeRepository implements RecipeRepositoryInterface
{
    public function __construct(
        protected readonly Item $model,
    ) {}

    public function syncIngredients(string $productId, array $ingredientsData): Item
    {
        $menuItem = $this->model->newQuery()->findOrFail($productId);

        if ($menuItem->item_type !== ItemType::MENU_ITEM) {
            throw ValidationException::withMessages([
                'product_id' => ['Chỉ món ăn (MENU_ITEM) mới có thể gán công thức.'],
            ]);
        }

        foreach ($ingredientsData as $index => $ingredient) {
            $ingredientItem = $this->model->newQuery()->find($ingredient['ingredient_id']);

            if (! $ingredientItem
                || $ingredientItem->item_type !== ItemType::INGREDIENT
                || $ingredientItem->restaurant_id !== $menuItem->restaurant_id
            ) {
                throw ValidationException::withMessages([
                    "ingredients.{$index}.ingredient_id" => ['Nguyên liệu không hợp lệ hoặc không thuộc nhà hàng này.'],
                ]);
            }
        }

        // ── Chuyển đổi format cho Eloquent sync() ────────────────────────────
        // Input:  [['ingredient_id' => 'uuid-1', 'quantity' => 0.200], ...]
        // Output: ['uuid-1' => ['quantity' => 0.200], ...]
        $syncData = [];
        foreach ($ingredientsData as $ingredient) {
            $syncData[$ingredient['ingredient_id']] = [
                'quantity' => $ingredient['quantity'],
            ];
        }

        $menuItem->ingredients()->sync($syncData);

        // ── Dispatch event để Listener tính lại giá vốn ─────────────────────
        RecipeUpdated::dispatch($menuItem);

        return $menuItem->load('ingredients');
    }
}
