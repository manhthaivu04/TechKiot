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

        // TODO [PREMIUM]: Implement inventory stock check (giống hệt CheckInventoryStockPipe)
        // $itemsMap = request()->attributes->get('_validated_add_items_map');
        // foreach ($dto->newItems as $itemDTO) {
        //     $item = $itemsMap->get($itemDTO->itemId);
        //     foreach ($item->recipes as $recipe) {
        //         $needed = $recipe->quantity_per_unit * $itemDTO->quantity;
        //         if ($recipe->ingredient->stock_quantity < $needed) {
        //             throw new \DomainException(
        //                 "Nguyên liệu [{$recipe->ingredient->name}] không đủ để chế biến món gọi thêm [{$item->name}]."
        //             );
        //         }
        //     }
        // }

        return $next($dto);
    }
}
