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

        // TODO [PREMIUM]: Implement inventory stock check
        // $itemsMap = request()->attributes->get('_validated_items_map');
        // foreach ($dto->items as $itemDTO) {
        //     $item = $itemsMap->get($itemDTO->itemId);
        //     foreach ($item->recipes as $recipe) {
        //         $needed = $recipe->quantity_per_unit * $itemDTO->quantity;
        //         if ($recipe->ingredient->stock_quantity < $needed) {
        //             throw new \DomainException(
        //                 "Nguyên liệu [{$recipe->ingredient->name}] không đủ để chế biến món [{$item->name}]."
        //             );
        //         }
        //     }
        // }

        return $next($dto);
    }
}
