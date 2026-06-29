<?php

namespace App\Listeners;

use App\Events\RecipeUpdated;
use App\Services\ItemCostPriceService;

/**
 * RecalculateItemCostPrice [PREMIUM — RECIPE_MANAGEMENT]
 *
 * Khi công thức (BOM) thay đổi → tính lại cost_price món ăn từ nguyên liệu.
 */
class RecalculateItemCostPrice
{
    public function __construct(
        private readonly ItemCostPriceService $itemCostPriceService,
    ) {}

    public function handle(RecipeUpdated $event): void
    {
        $this->itemCostPriceService->recalculateMenuItemCost($event->menuItem);
    }
}
