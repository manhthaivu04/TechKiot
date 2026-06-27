<?php

namespace App\Listeners;

use App\Enums\TransactionType;
use App\Events\OrderPlaced;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DeductInventoryListener [PREMIUM — INVENTORY_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Trừ nguyên liệu trong kho khi có đơn hàng mới được tạo.
 * Trước khi trừ, kiểm tra nhà hàng có đăng ký gói Premium (INVENTORY_MANAGEMENT) không.
 * Nếu KHÔNG → bỏ qua hoàn toàn (Basic/Pro users không bị ảnh hưởng).
 * Nếu CÓ → trừ nguyên liệu tương ứng từng món theo BOM (item_ingredients).
 *
 * ── Flow ──────────────────────────────────────────────────────────────────────
 * OrderPlaced → Guard (feature check) → Tìm kho mặc định
 *   → Loop orderItems → Load BOM → Tính neededQty
 *   → Trừ Inventory → Ghi InventoryTransaction (recipe_use)
 *
 * ── Business Rule ─────────────────────────────────────────────────────────────
 * Cho phép tồn kho ÂM (negative stock). Lý do:
 * - Không block đơn hàng khi nguyên liệu chưa kịp nhập.
 * - Tồn âm = tín hiệu cần nhập thêm hàng (report sẽ highlight).
 * - Log WARNING khi tồn < 0 để staff theo dõi.
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

        // ── Tìm kho mặc định của nhà hàng ─────────────────────────────────────
        $defaultWarehouse = Warehouse::where('restaurant_id', $restaurant->id)
            ->where('is_default', true)
            ->first();

        if (! $defaultWarehouse) {
            Log::warning("[INVENTORY] Nhà hàng [{$restaurant->id}] chưa có kho mặc định. Bỏ qua trừ kho.");
            return;
        }

        // ── Eager load BOM để tránh N+1 ────────────────────────────────────────
        $order->load('items.item.ingredients');

        // ── Unit of Work: toàn bộ trừ kho trong 1 transaction ──────────────────
        DB::transaction(function () use ($order, $restaurant, $defaultWarehouse) {
            $totalDeducted = 0;

            foreach ($order->items as $orderItem) {
                // Món không có BOM (ingredients rỗng) → bỏ qua
                if (! $orderItem->item || $orderItem->item->ingredients->isEmpty()) {
                    continue;
                }

                foreach ($orderItem->item->ingredients as $ingredient) {
                    // Tính lượng nguyên liệu cần trừ
                    // = (lượng NL cho 1 đơn vị món) × (số lượng món đặt)
                    $neededQty = (float) $ingredient->pivot->quantity * (float) $orderItem->quantity;

                    // Tìm hoặc tạo dòng Inventory cho nguyên liệu này
                    $inventory = Inventory::firstOrCreate(
                        [
                            'restaurant_id' => $restaurant->id,
                            'warehouse_id'  => $defaultWarehouse->id,
                            'item_id'       => $ingredient->id,
                        ],
                        ['quantity' => 0]
                    );

                    $beforeQty = (float) $inventory->quantity;
                    $afterQty  = $beforeQty - $neededQty;

                    // Cập nhật tồn kho (cho phép âm)
                    $inventory->update(['quantity' => $afterQty]);

                    // Ghi sổ kho — Immutable Audit Log
                    InventoryTransaction::create([
                        'restaurant_id'    => $restaurant->id,
                        'warehouse_id'     => $defaultWarehouse->id,
                        'item_id'          => $ingredient->id,
                        'transaction_type' => TransactionType::RECIPE_USE->value,
                        'reference_type'   => 'order',
                        'reference_id'     => $order->id,
                        'quantity_change'   => round(-$neededQty, 3),
                        'before_quantity'  => $beforeQty,
                        'after_quantity'   => $afterQty,
                        'note'             => "Auto-deduct: [{$orderItem->item->name}] x{$orderItem->quantity}",
                        'created_by'       => $order->created_by,
                    ]);

                    // Cảnh báo nếu tồn kho âm
                    if ($afterQty < 0) {
                        Log::warning("[INVENTORY] Tồn kho ÂM", [
                            'ingredient'  => $ingredient->name,
                            'ingredient_id' => $ingredient->id,
                            'warehouse'   => $defaultWarehouse->name,
                            'after_qty'   => $afterQty,
                            'order_code'  => $order->order_code,
                        ]);
                    }

                    $totalDeducted++;
                }
            }

            Log::info("[INVENTORY] Trừ kho hoàn tất", [
                'order_code'     => $order->order_code,
                'warehouse'      => $defaultWarehouse->name,
                'total_deducted' => $totalDeducted,
            ]);
        });
    }
}
