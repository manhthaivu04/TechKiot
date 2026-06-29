<?php

namespace App\Listeners;

use App\Events\IngredientCostPriceUpdated;
use App\Services\ItemCostPriceService;

/**
 * Khi giá vốn nguyên liệu thay đổi → tính lại giá vốn các món dùng nguyên liệu đó.
 */
class RecalculateMenuItemsOnIngredientCostChange
{
    public function __construct(
        private readonly ItemCostPriceService $itemCostPriceService,
    ) {}

    public function handle(IngredientCostPriceUpdated $event): void
    {
        $this->itemCostPriceService->recalculateMenuItemsUsingIngredient($event->ingredient);
    }
}
