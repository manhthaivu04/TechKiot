<?php

namespace App\Services\Menu\Strategies;

use App\Contracts\Menu\MenuSourceStrategy;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\DTOs\GetMenuDTO;
use App\Services\Menu\MenuGrouper;

/**
 * Strategy menu cho Tenant POS — gọi món tại quầy / thu ngân.
 *
 * Chỉ trả về món bán (MENU_ITEM) đang active và còn hàng,
 * không bao gồm nguyên liệu (INGREDIENT) dùng cho kho.
 */
class TenantPosMenuStrategy implements MenuSourceStrategy
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly MenuGrouper             $menuGrouper,
    ) {}

    public function getMenu(GetMenuDTO $dto): array
    {
        if (! $dto->restaurantId) {
            throw new \InvalidArgumentException('restaurantId là bắt buộc cho tenant_pos menu.');
        }

        $items = $this->itemRepository->getActiveMenuByRestaurantId($dto->restaurantId);

        return [
            'item_groups' => $this->menuGrouper->group($items),
        ];
    }
}
