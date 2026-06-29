<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Events\IngredientCostPriceUpdated;
use App\Models\Inventory;
use App\Models\Item;
use Illuminate\Support\Facades\Log;

/**
 * ItemCostPriceService — tính và cập nhật giá vốn nguyên liệu / món ăn.
 *
 * - INGREDIENT: giá đơn vị, cập nhật bằng bình quân gia quyền khi nhập kho.
 * - MENU_ITEM:  giá vốn = Σ (ingredient.cost_price × pivot.quantity).
 */
class ItemCostPriceService
{
    public function calculateWeightedAverage(
        float $currentQuantity,
        float $currentCost,
        float $receiptQuantity,
        float $unitCost,
    ): float {
        if ($receiptQuantity <= 0) {
            return $currentCost;
        }

        if ($currentQuantity <= 0) {
            return round($unitCost, 2);
        }

        $totalValue = ($currentQuantity * $currentCost) + ($receiptQuantity * $unitCost);
        $totalQuantity = $currentQuantity + $receiptQuantity;

        return round($totalValue / $totalQuantity, 2);
    }

    public function getTotalInventoryQuantity(string $restaurantId, string $itemId): float
    {
        return (float) Inventory::query()
            ->where('restaurant_id', $restaurantId)
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    public function updateIngredientCost(Item $ingredient, float $newCost): void
    {
        if ($ingredient->item_type !== ItemType::INGREDIENT) {
            return;
        }

        $oldCost = (float) $ingredient->cost_price;

        if (round($oldCost, 2) === round($newCost, 2)) {
            return;
        }

        $ingredient->update(['cost_price' => $newCost]);

        Log::info("[COST] Nguyên liệu [{$ingredient->name}] cập nhật giá vốn: "
            . number_format($oldCost, 2) . ' → ' . number_format($newCost, 2));

        IngredientCostPriceUpdated::dispatch($ingredient->refresh());
    }

    public function recalculateMenuItemCost(Item $menuItem): void
    {
        if ($menuItem->item_type !== ItemType::MENU_ITEM) {
            return;
        }

        $menuItem->load('ingredients');

        $totalCost = $menuItem->ingredients->sum(function (Item $ingredient) {
            return (float) $ingredient->pivot->quantity * (float) $ingredient->cost_price;
        });

        $menuItem->update(['cost_price' => round($totalCost, 2)]);

        Log::info("[COST] Món [{$menuItem->name}] giá vốn: " . number_format($totalCost, 2) . ' VND');
    }

    public function recalculateMenuItemsUsingIngredient(Item $ingredient): void
    {
        $ingredient->load('usedAsIngredientIn');

        foreach ($ingredient->usedAsIngredientIn as $menuItem) {
            $this->recalculateMenuItemCost($menuItem);
        }
    }
}
