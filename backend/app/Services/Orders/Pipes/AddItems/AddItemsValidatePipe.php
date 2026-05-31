<?php

namespace App\Services\Orders\Pipes\AddItems;

use App\Contracts\Orders\AddItemsPipeInterface;
use App\DTOs\AddItemsDTO;
use App\Models\Item;
use Closure;
use Illuminate\Database\Eloquent\Collection;

class AddItemsValidatePipe implements AddItemsPipeInterface
{
    public function handle(AddItemsDTO $dto, Closure $next): mixed
    {
        // 1. Kiểm tra State: Đơn đã thanh toán/hủy thì không được thêm
        if (! $dto->order->state()->canAddItems()) {
            throw new \DomainException("Không thể thêm món vào đơn hàng đã thanh toán hoặc đã hủy.");
        }

        if (empty($dto->newItems)) {
            return $next($dto);
        }

        $itemIds = array_map(fn($item) => $item->itemId, $dto->newItems);

        // 2. Batch Query kiểm tra món
        /** @var Collection<string, Item> $itemsMap */
        $itemsMap = Item::query()
            ->whereIn('id', $itemIds)
            ->where('restaurant_id', $dto->order->restaurant_id)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        foreach ($dto->newItems as $itemDTO) {
            if (! $itemsMap->has($itemDTO->itemId)) {
                throw new \DomainException("Món [{$itemDTO->itemId}] không tồn tại hoặc đã ngưng bán.");
            }
        }

        // Truyền context sang Pipe tính tiền
        request()->attributes->set('_validated_add_items_map', $itemsMap);

        return $next($dto);
    }
}
