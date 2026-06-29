<?php

namespace App\Events;

use App\Models\Item;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * IngredientCostPriceUpdated — giá vốn nguyên liệu thay đổi.
 *
 * Trigger: nhập kho (weighted average) hoặc cập nhật thủ công qua API.
 */
class IngredientCostPriceUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Item $ingredient,
    ) {}
}
