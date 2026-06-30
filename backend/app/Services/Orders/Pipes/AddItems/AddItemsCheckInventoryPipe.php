<?php

namespace App\Services\Orders\Pipes\AddItems;

use App\Contracts\Orders\AddItemsPipeInterface;
use App\DTOs\AddItemsDTO;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * AddItemsCheckInventoryPipe [PREMIUM — INVENTORY_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Kiểm tra tồn kho nguyên liệu trước khi cho phép gọi thêm món.
 * Tương tự như CheckInventoryStockPipe trong PlaceOrderAction, Pipe này
 * đóng vai trò là "guard" chặn lại nếu nhà hàng hết nguyên liệu.
 *
 * Chỉ hoạt động với các nhà hàng có gói PREMIUM (có feature INVENTORY_MANAGEMENT).
 */
class AddItemsCheckInventoryPipe implements AddItemsPipeInterface
{
    public function handle(AddItemsDTO $dto, Closure $next): mixed
    {
        $restaurant = $dto->order->restaurant; // Lấy restaurant trực tiếp từ Order

        // ── Guard: Chỉ check kho với gói Premium ─────────────────────────────
        if (! $restaurant?->hasFeature('INVENTORY_MANAGEMENT')) {
            return $next($dto); // Basic/Pro: bỏ qua hoàn toàn
        }

        Log::debug("[INVENTORY] Checking stock for adding items to order [{$dto->order->id}]...");

        $defaultWarehouse = \App\Models\Warehouse::where('restaurant_id', $restaurant->id)
            ->where('is_default', true)
            ->first();

        if (! $defaultWarehouse) {
            Log::warning("[INVENTORY] Nhà hàng [{$restaurant->id}] chưa có kho mặc định. Bỏ qua check kho.");
            return $next($dto);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Item> $itemsMap */
        $itemsMap = request()->attributes->get('_validated_add_items_map');
        if ($itemsMap) {
            $itemsMap->loadMissing('ingredients');
        }

        $neededIngredients = [];

        foreach ($dto->newItems as $itemDTO) {
            $item = $itemsMap ? $itemsMap->get($itemDTO->itemId) : \App\Models\Item::with('ingredients')->find($itemDTO->itemId);
            
            if (! $item || $item->ingredients->isEmpty()) {
                continue;
            }

            foreach ($item->ingredients as $ingredient) {
                $needed = (float) $ingredient->pivot->quantity * (float) $itemDTO->quantity;
                
                if (!isset($neededIngredients[$ingredient->id])) {
                    $neededIngredients[$ingredient->id] = [
                        'name' => $ingredient->name,
                        'needed' => 0,
                        'itemNames' => [],
                    ];
                }
                $neededIngredients[$ingredient->id]['needed'] += $needed;
                if (!in_array($item->name, $neededIngredients[$ingredient->id]['itemNames'])) {
                    $neededIngredients[$ingredient->id]['itemNames'][] = $item->name;
                }
            }
        }

        if (empty($neededIngredients)) {
            return $next($dto);
        }

        $inventories = \App\Models\Inventory::where('restaurant_id', $restaurant->id)
            ->where('warehouse_id', $defaultWarehouse->id)
            ->whereIn('item_id', array_keys($neededIngredients))
            ->get()
            ->keyBy('item_id');

        foreach ($neededIngredients as $ingredientId => $data) {
            $inventory = $inventories->get($ingredientId);
            $currentStock = $inventory ? (float) $inventory->quantity : 0;

            if ($currentStock < $data['needed']) {
                $itemNames = implode(', ', $data['itemNames']);
                throw new \DomainException(
                    "Nguyên liệu [{$data['name']}] không đủ trong kho để gọi thêm món [{$itemNames}]. (Cần: {$data['needed']}, Tồn: {$currentStock})"
                );
            }
        }

        return $next($dto);
    }
}
