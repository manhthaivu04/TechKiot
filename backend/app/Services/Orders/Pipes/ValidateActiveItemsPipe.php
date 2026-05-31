<?php

namespace App\Services\Orders\Pipes;

use App\Contracts\Orders\OrderPipeInterface;
use App\DTOs\PlaceOrderDTO;
use App\Models\Item;
use Closure;
use Illuminate\Database\Eloquent\Collection;

/**
 * ValidateActiveItemsPipe [BASIC]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Batch-query toàn bộ Items được yêu cầu, kiểm tra:
 * 1. Item có tồn tại không.
 * 2. Item có thuộc về nhà hàng không (tenant isolation).
 * 3. Item có đang active (is_active = true) không.
 *
 * ── Pattern: Fail-Fast ───────────────────────────────────────────────────────
 * Validate tất cả items trước khi đặt DB transaction → tránh tạo nửa vời.
 * Ném DomainException nếu bất kỳ item nào không hợp lệ → Pipeline dừng lại.
 *
 * ── Tại sao là Pipe riêng? ───────────────────────────────────────────────────
 * Trước đây logic này nằm trong PlaceOrderAction (vi phạm SRP).
 * Tách ra Pipe → PlaceOrderAction chỉ orchestrate, không validate trực tiếp.
 */
class ValidateActiveItemsPipe implements OrderPipeInterface
{
    public function handle(PlaceOrderDTO $dto, Closure $next): mixed
    {
        $itemIds = array_map(fn($item) => $item->itemId, $dto->items);

        // Batch query — chỉ 1 lần truy vấn DB cho toàn bộ items
        /** @var Collection<string, Item> $itemsMap */
        $itemsMap = Item::query()
            ->whereIn('id', $itemIds)
            ->where('restaurant_id', $dto->restaurantId)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // Validate tất cả trước khi cho qua
        foreach ($dto->items as $itemDTO) {
            if (! $itemsMap->has($itemDTO->itemId)) {
                throw new \DomainException(
                    "Món [{$itemDTO->itemId}] không tồn tại, không thuộc nhà hàng, hoặc đã ngưng bán."
                );
            }
        }

        // Đính kèm itemsMap vào DTO để Pipe tiếp theo (CalculatePricingPipe)
        // tái sử dụng mà không cần query lại DB lần nữa.
        // Vì PlaceOrderDTO là readonly, ta dùng một "túi tạm" qua request attributes.
        request()->attributes->set('_validated_items_map', $itemsMap);

        return $next($dto);
    }
}
