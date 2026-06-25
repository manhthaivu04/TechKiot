<?php

namespace App\Services\Menu\Strategies;

use App\Contracts\Menu\MenuSourceStrategy;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\DTOs\GetMenuDTO;
use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\RestaurantTable;
use App\Services\Menu\MenuGrouper;

/**
 * Strategy cho QR bàn (QR_TABLE_ORDER) — Feature: TABLE_MANAGEMENT (Gói Pro).
 *
 * Khác với QrStaticMenuStrategy:
 * - public_token = qr_token của bàn (không phải restaurant_id)
 * - Cần resolve: qr_token → RestaurantTable → restaurant_id
 * - Trả thêm thông tin bàn + active_order (nếu bàn đang có khách ăn)
 *   để khách hàng gọi thêm món vào đơn hiện tại thay vì tạo đơn mới.
 *
 * Cấu trúc response:
 * {
 *   "table":        { id, name, capacity, status },
 *   "active_order": OrderResource | null,
 *   "menu":         [...grouped items]
 * }
 */
class QrTableMenuStrategy implements MenuSourceStrategy
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly MenuGrouper             $menuGrouper,
    ) {}

    /**
     * {@inheritdoc}
     *
     * public_token = qr_token dán trên bàn.
     * Validate bằng firstOrFail — 404 nếu qr_token không tồn tại.
     */
    public function getMenu(GetMenuDTO $dto): array
    {
        // 1. Validate token → tìm RestaurantTable
        $table = RestaurantTable::with('area', 'restaurant')
            ->where('qr_token', $dto->publicToken)
            ->firstOrFail();

        if (! $table->restaurant->hasFeature('TABLE_MANAGEMENT')) {
            abort(403, 'Nhà hàng chưa đăng ký hoặc đã hết hạn tính năng gọi món tại bàn.');
        }

        // 2. Tìm Order đang active của bàn này (open | cooking | served)
        $activeOrder = $table->orders()
            ->whereIn('status', [
                OrderStatus::Open->value,
                OrderStatus::Cooking->value,
                OrderStatus::Served->value,
            ])
            ->with(['items.item', 'payments'])
            ->latest()
            ->first();

        // 3. Lấy menu items và group
        $items = $this->itemRepository->getActiveMenuByRestaurantId($table->restaurant_id);
        $groupedMenu = $this->menuGrouper->group($items);

        // 4. Trả về cả menu + context bàn + active order
        return [
            'restaurant' => [
                'id'         => $table->restaurant->id,
                'name'       => $table->restaurant->name,
                'address'    => $table->restaurant->address,
                'phone'      => $table->restaurant->phone,
                'banner_url' => $table->restaurant->banner_url,
            ],
            'table' => [
                'id'       => $table->id,
                'name'     => $table->name,
                'capacity' => $table->capacity,
                'status'   => $table->status?->value,
                'area'     => $table->area ? [
                    'id'   => $table->area->id,
                    'name' => $table->area->name,
                ] : null,
            ],
            'active_order' => $activeOrder
                ? (new OrderResource($activeOrder))->resolve()
                : null,
            'item_groups' => $groupedMenu,
        ];
    }
}
