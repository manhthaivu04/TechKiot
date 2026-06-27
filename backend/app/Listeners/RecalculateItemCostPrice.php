<?php

namespace App\Listeners;

use App\Events\RecipeUpdated;
use Illuminate\Support\Facades\Log;

/**
 * RecalculateItemCostPrice [PREMIUM — RECIPE_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Khi công thức (BOM) của một Món ăn thay đổi, tính lại giá vốn (cost_price)
 * bằng công thức:
 *
 *   cost_price = Σ (ingredient.cost_price × pivot.quantity)
 *
 * VD: Phở bò gồm:
 *   - 0.200 kg Thịt bò  (cost_price = 150,000/kg)  → 30,000
 *   - 0.300 kg Bánh phở  (cost_price = 20,000/kg)   →  6,000
 *   - 0.500 lít Nước dùng (cost_price = 10,000/lít) →  5,000
 *   ─────────────────────────────────────────────────
 *   cost_price(Phở bò) = 41,000
 *
 * ── Tại sao dùng Listener thay vì tính trong Repository? ─────────────────────
 * 1. SRP: Repository lo sync pivot, Listener lo tính toán.
 * 2. Dễ mở rộng: Sau này có thể thêm Listener khác (VD: NotifyChefListener).
 * 3. Dễ test: Mock event, kiểm tra cost_price tính đúng.
 */
class RecalculateItemCostPrice
{
    public function handle(RecipeUpdated $event): void
    {
        $menuItem = $event->menuItem;

        // ── Eager load ingredients kèm cost_price ────────────────────────────
        $menuItem->load('ingredients');

        // ── Tính tổng giá vốn ────────────────────────────────────────────────
        $totalCost = $menuItem->ingredients->sum(function ($ingredient) {
            return (float) $ingredient->pivot->quantity * (float) $ingredient->cost_price;
        });

        // ── Update đè vào cost_price của Món ăn ─────────────────────────────
        $menuItem->update(['cost_price' => round($totalCost, 2)]);

        Log::info("[RECIPE] Đã tính lại giá vốn cho [{$menuItem->name}]: " . number_format($totalCost, 2) . " VND");
    }
}
