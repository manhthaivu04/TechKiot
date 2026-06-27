<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RecipeRepositoryInterface;
use App\Events\RecipeUpdated;
use App\Models\Item;

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
