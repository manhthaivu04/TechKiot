<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

/**
 * DeductInventoryListener [PREMIUM — INVENTORY_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Trừ nguyên liệu trong kho khi có đơn hàng mới được tạo.
 * Trước khi trừ, kiểm tra nhà hàng có đăng ký gói Premium (INVENTORY_MANAGEMENT) không.
 * Nếu KHÔNG → bỏ qua hoàn toàn (Basic/Pro users không bị ảnh hưởng).
 * Nếu CÓ → trừ nguyên liệu tương ứng từng món.
 *
 * ── Lý do không kiểm tra kho TRONG PlaceOrderAction ─────────────────────────
 * Nếu check trong Action → vi phạm OCP (mỗi lần thêm gói phải sửa Action).
 * Với Observer Pattern → thêm Listener mới, Action không thay đổi gì.
 *
 * ── TODO (khi implement Premium) ─────────────────────────────────────────────
 * 1. Implement bảng `inventory_items` và `item_recipe` trong DB.
 * 2. Uncomment logic trừ kho bên dưới.
 * 3. Đăng ký Listener trong EventServiceProvider.
 * Không cần sửa PlaceOrderAction hay bất kỳ class nào hiện có.
 */
class DeductInventoryListener
{
    public function handle(OrderPlaced $event): void
    {
        $order      = $event->order;
        $restaurant = $order->restaurant;

        // ── Guard: Chỉ chạy với gói Premium có tính năng INVENTORY_MANAGEMENT ──
        if (! $restaurant->hasFeature('INVENTORY_MANAGEMENT')) {
            return; // Silently skip — Basic/Pro restaurants không bị ảnh hưởng
        }

        Log::info("[INVENTORY] Đang trừ nguyên liệu cho đơn [{$order->order_code}]...");

        // TODO [PREMIUM]: Implement inventory deduction logic
        // foreach ($order->items as $orderItem) {
        //     $recipes = $orderItem->item->recipes; // Item → Recipe → Ingredient
        //     foreach ($recipes as $recipe) {
        //         $neededAmount = $recipe->quantity_per_unit * $orderItem->quantity;
        //         $recipe->ingredient->deduct($neededAmount); // throws InsufficientStockException
        //     }
        // }
    }
}
