<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Events\OrderItemRemoved;
use App\Events\OrderItemUpdated;
use App\Events\OrderStatusTransitioned;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AdjustInventoryOnOrderChangesListener [PREMIUM — INVENTORY_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Xử lý hoàn/điều chỉnh tồn kho khi:
 * 1. Hủy đơn hàng (OrderStatusTransitioned sang Cancelled).
 * 2. Hủy món (OrderItemRemoved).
 * 3. Thay đổi số lượng món (OrderItemUpdated).
 *
 * ── Luồng xử lý ───────────────────────────────────────────────────────────────
 * Guard kiểm tra feature INVENTORY_MANAGEMENT. Nếu hợp lệ, lấy kho mặc định,
 * rồi thực hiện tính toán lại số lượng nguyên liệu cần hoàn/trừ dựa vào BOM
 * của từng item bị ảnh hưởng.
 */
class AdjustInventoryOnOrderChangesListener
{
    public function handle(OrderStatusTransitioned|OrderItemRemoved|OrderItemUpdated|\App\Events\OrderItemsAdded $event): void
    {
        $order = $event->order;
        $restaurant = $order->restaurant ?? $order->restaurant()->first();

        // ── Guard ──
        if (! $restaurant || ! $restaurant->hasFeature('INVENTORY_MANAGEMENT')) {
            return;
        }

        $defaultWarehouse = Warehouse::where('restaurant_id', $restaurant->id)
            ->where('is_default', true)
            ->first();

        if (! $defaultWarehouse) {
            Log::warning("[INVENTORY] Nhà hàng [{$restaurant->id}] chưa có kho mặc định. Bỏ qua hoàn kho.");
            return;
        }

        if ($event instanceof OrderStatusTransitioned) {
            $this->handleOrderStatusTransitioned($event, $defaultWarehouse);
        } elseif ($event instanceof OrderItemRemoved) {
            $this->handleOrderItemRemoved($event, $defaultWarehouse);
        } elseif ($event instanceof OrderItemUpdated) {
            $this->handleOrderItemUpdated($event, $defaultWarehouse);
        } elseif ($event instanceof \App\Events\OrderItemsAdded) {
            $this->handleOrderItemsAdded($event, $defaultWarehouse);
        }
    }

    /**
     * Gọi thêm món -> Trừ nguyên liệu của món gọi thêm.
     */
    private function handleOrderItemsAdded(\App\Events\OrderItemsAdded $event, Warehouse $warehouse): void
    {
        $order = $event->order;

        DB::transaction(function () use ($warehouse, $order, $event) {
            foreach ($event->newItems as $dto) {
                $item = \App\Models\Item::with('ingredients')->find($dto->itemId);
                if (! $item || $item->ingredients->isEmpty()) {
                    continue;
                }

                $this->adjustInventory(
                    $warehouse, 
                    $order, 
                    $item, 
                    -(float) $dto->quantity, // Truyền số âm để trừ kho
                    "Trừ kho (Gọi thêm món): [{$item->name}] x{$dto->quantity}"
                );
            }
            Log::info("[INVENTORY] Trừ kho hoàn tất (Gọi thêm món).", [
                'order_code' => $order->order_code
            ]);
        });
    }

    /**
     * Hủy đơn hàng -> Hoàn lại nguyên liệu của TẤT CẢ các món trong đơn.
     */
    private function handleOrderStatusTransitioned(OrderStatusTransitioned $event, Warehouse $warehouse): void
    {
        if ($event->to !== OrderStatus::Cancelled) {
            return;
        }

        $order = $event->order;
        $order->load('items.item.ingredients');

        DB::transaction(function () use ($order, $warehouse) {
            foreach ($order->items as $orderItem) {
                if (! $orderItem->item || $orderItem->item->ingredients->isEmpty()) {
                    continue;
                }

                $this->adjustInventory(
                    $warehouse, 
                    $order, 
                    $orderItem->item, 
                    (float) $orderItem->quantity, // Hoàn lại (+) toàn bộ số lượng của item
                    "Hoàn kho (Hủy đơn): [{$orderItem->item->name}] x{$orderItem->quantity}"
                );
            }
            Log::info("[INVENTORY] Hoàn kho hoàn tất (Đơn hàng bị hủy).", ['order_code' => $order->order_code]);
        });
    }

    /**
     * Hủy 1 món -> Hoàn lại nguyên liệu của món đó.
     */
    private function handleOrderItemRemoved(OrderItemRemoved $event, Warehouse $warehouse): void
    {
        $order = $event->order;
        $removedItemData = $event->removedItemData;
        $quantity = (float) ($removedItemData['quantity'] ?? 0);
        $itemId = $removedItemData['item_id'] ?? null;

        if (! $itemId || $quantity <= 0) {
            return;
        }

        $item = \App\Models\Item::with('ingredients')->find($itemId);
        if (! $item || $item->ingredients->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($warehouse, $order, $item, $quantity) {
            $this->adjustInventory(
                $warehouse, 
                $order, 
                $item, 
                $quantity, // Hoàn lại (+) số lượng đã xóa
                "Hoàn kho (Xóa món): [{$item->name}] x{$quantity}"
            );
            Log::info("[INVENTORY] Hoàn kho hoàn tất (Xóa món).", [
                'order_code' => $order->order_code,
                'item_id' => $item->id
            ]);
        });
    }

    /**
     * Thay đổi số lượng món -> Điều chỉnh kho dựa trên số lượng chênh lệch.
     */
    private function handleOrderItemUpdated(OrderItemUpdated $event, Warehouse $warehouse): void
    {
        $order = $event->order;
        $orderItem = $event->orderItem;
        $oldQuantity = $event->oldQuantity;

        if ($oldQuantity === null || $oldQuantity === $orderItem->quantity) {
            return; // Không có sự thay đổi về quantity
        }

        $diffQuantity = $orderItem->quantity - $oldQuantity;
        // diff > 0: Tăng món -> phải TRỪ kho. Truyền vào quantity âm để hàm adjustInventory thực hiện trừ.
        // diff < 0: Giảm món -> phải HOÀN kho. Truyền vào quantity dương để cộng.
        $qtyToAdjust = -$diffQuantity; 

        $item = $orderItem->item ?? $orderItem->item()->with('ingredients')->first();
        if (! $item || $item->ingredients->isEmpty()) {
            return;
        }

        $actionName = $qtyToAdjust > 0 ? "Hoàn kho (Giảm món)" : "Trừ kho (Tăng món)";

        DB::transaction(function () use ($warehouse, $order, $item, $qtyToAdjust, $actionName, $diffQuantity) {
            $this->adjustInventory(
                $warehouse, 
                $order, 
                $item, 
                $qtyToAdjust,
                "{$actionName}: [{$item->name}] chênh lệch {$diffQuantity}"
            );
            Log::info("[INVENTORY] Điều chỉnh kho hoàn tất (Cập nhật món).", [
                'order_code' => $order->order_code,
                'item_id' => $item->id,
                'diff_quantity' => $diffQuantity
            ]);
        });
    }

    /**
     * Hàm helper thực hiện việc điều chỉnh từng ingredient của một item.
     * @param float $quantityToAdjust Nếu > 0: hoàn kho (cộng). Nếu < 0: trừ kho (trừ).
     */
    private function adjustInventory(Warehouse $warehouse, Order $order, \App\Models\Item $item, float $quantityToAdjust, string $note): void
    {
        foreach ($item->ingredients as $ingredient) {
            // Lượng thay đổi của nguyên liệu = (lượng định mức 1 phần) * (số lượng món thay đổi)
            $qtyChange = (float) $ingredient->pivot->quantity * $quantityToAdjust;

            $inventory = Inventory::firstOrCreate(
                [
                    'restaurant_id' => $warehouse->restaurant_id,
                    'warehouse_id'  => $warehouse->id,
                    'item_id'       => $ingredient->id,
                ],
                ['quantity' => 0]
            );

            $beforeQty = (float) $inventory->quantity;
            $afterQty  = $beforeQty + $qtyChange;

            $inventory->update(['quantity' => $afterQty]);

            // Xác định transaction type
            // Hoàn/trừ đều có thể quy về RECIPE_USE hoặc một loại riêng biệt như ADJUSTMENT
            // Hiện tại ta dùng RECIPE_USE (nhưng với số lượng dương/âm tương ứng) 
            // để gộp chung logic báo cáo sử dụng BOM.
            InventoryTransaction::create([
                'restaurant_id'    => $warehouse->restaurant_id,
                'warehouse_id'     => $warehouse->id,
                'item_id'          => $ingredient->id,
                'transaction_type' => TransactionType::RECIPE_USE->value,
                'reference_type'   => 'order',
                'reference_id'     => $order->id,
                'quantity_change'  => round($qtyChange, 3),
                'before_quantity'  => $beforeQty,
                'after_quantity'   => $afterQty,
                'note'             => $note,
                'created_by'       => $order->created_by,
            ]);
        }
    }
}
