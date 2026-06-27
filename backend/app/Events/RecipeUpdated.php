<?php

namespace App\Events;

use App\Models\Item;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecipeUpdated — Event phát ra sau khi công thức (BOM) của Món ăn bị thay đổi.
 *
 * ── Observer Pattern ─────────────────────────────────────────────────────────
 * RecipeRepository không cần biết giá vốn được tính như thế nào —
 * chỉ cần fire event, Listener lo phần còn lại.
 *
 * Listeners đăng ký trong EventServiceProvider:
 *   ┌────────────────────────────────────────────────────────────┐
 *   │ [PREMIUM] RecalculateItemCostPrice → Tính lại cost_price  │
 *   └────────────────────────────────────────────────────────────┘
 */
class RecipeUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Item $menuItem,
    ) {}
}
