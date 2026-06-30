<?php

namespace App\Services\Orders\Pipes;

use App\Contracts\Orders\OrderPipeInterface;
use App\DTOs\PlaceOrderDTO;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * CheckInventoryStockPipe [PREMIUM — INVENTORY_MANAGEMENT]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Kiểm tra tồn kho nguyên liệu trước khi cho phép đặt đơn.
 * Nếu nhà hàng KHÔNG có gói Premium → bỏ qua hoàn toàn (pass-through).
 * Nếu CÓ gói Premium → check từng nguyên liệu trong recipe của mỗi món.
 *
 * ── Lý do có Pipe "rỗng" này trong Basic ─────────────────────────────────────
 * Khuôn sẵn để sau chỉ cần implement logic bên trong — KHÔNG cần sửa cấu trúc.
 * Pipeline vẫn chạy qua Pipe này ở tất cả gói, nhưng guard `hasFeature()`
 * đảm bảo Basic/Pro user không bị ảnh hưởng.
 *
 * ── TODO (khi implement Premium) ─────────────────────────────────────────────
 * 1. Tạo bảng `inventory_items`, `item_recipes` trong migration.
 * 2. Implement InventoryRepository.
 * 3. Uncomment logic kiểm tra bên dưới.
 */
class CheckInventoryStockPipe implements OrderPipeInterface
{
    public function handle(PlaceOrderDTO $dto, Closure $next): mixed
    {
        $restaurant = \App\Models\Restaurant::find($dto->restaurantId);

        // ── Guard: Chỉ check kho với gói Premium ─────────────────────────────
        if (! $restaurant?->hasFeature('INVENTORY_MANAGEMENT')) {
            return $next($dto); // Basic/Pro: bỏ qua hoàn toàn
        }

        Log::debug("[INVENTORY] Checking stock for restaurant [{$dto->restaurantId}]...");

        $defaultWarehouse = \App\Models\Warehouse::where('restaurant_id', $restaurant->id)
            ->where('is_default', true)
            ->first();

        if (! $defaultWarehouse) {
            Log::warning("[INVENTORY] Nhà hàng [{$restaurant->id}] chưa có kho mặc định. Bỏ qua check kho.");
            return $next($dto);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Item> $itemsMap */
        $itemsMap = request()->attributes->get('_validated_items_map');
        if ($itemsMap) {
            $itemsMap->loadMissing('ingredients');
        }

        $neededIngredients = [];

        foreach ($dto->items as $itemDTO) {
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
                    "Nguyên liệu [{$data['name']}] không đủ trong kho để chế biến món [{$itemNames}]. (Cần: {$data['needed']}, Tồn: {$currentStock})"
                );
            }
        }

        return $next($dto);
    }
}
