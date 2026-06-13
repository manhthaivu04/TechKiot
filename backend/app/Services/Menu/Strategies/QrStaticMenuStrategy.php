<?php

namespace App\Services\Menu\Strategies;

use App\Contracts\Menu\MenuSourceStrategy;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\DTOs\GetMenuDTO;
use App\Models\Restaurant;
use App\Services\Menu\MenuGrouper;

/**
 * Strategy cho loại QR tĩnh của nhà hàng — Feature: QR_STATIC_ORDER.
 *
 * ── Tương đồng với QrStaticOrderStrategy ────────────────────────────────────
 * QrStaticOrderStrategy::handle()  → nhận đơn từ QR tĩnh, log + notify.
 * QrStaticMenuStrategy::getMenu()  → lấy menu từ QR tĩnh, resolve restaurant.
 *
 * Đặc điểm QR tĩnh:
 * - public_token  = restaurant_id (UUID nhà hàng).
 * - Mã QR được in ra, dán lên bàn, không thay đổi theo thời gian.
 * - Không biết khách đang ngồi bàn số mấy.
 *
 * Đặc điểm QR bàn (Pro — QrTableMenuStrategy):
 * - public_token  = table_id.
 * - Mã QR gắn vào từng bàn cụ thể, cần resolve table → restaurant.
 * - Có thể trả thêm thông tin bàn (table_name, session_id…).
 *
 * ── Trách nhiệm của class này (SRP) ─────────────────────────────────────────
 * 1. Validate public_token là một restaurant_id tồn tại.
 * 2. Gọi ItemRepository lấy items active.
 * 3. Đẩy items vào MenuGrouper để gom nhóm.
 * MenuService KHÔNG biết các bước này — nó chỉ gọi getMenu().
 */
class QrStaticMenuStrategy implements MenuSourceStrategy
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly MenuGrouper             $menuGrouper,
    ) {}

    /**
     * {@inheritdoc}
     *
     * Với QR tĩnh: public_token chính là restaurant_id.
     * Validate bằng cách findOrFail — nếu không tồn tại sẽ throw 404 tự động.
     */
    public function getMenu(GetMenuDTO $dto): array
    {
        // 1. Validate token: tìm restaurant theo public_order_token
        //    (firstOrFail throw ModelNotFoundException → Laravel render 404)
        $restaurant = Restaurant::where('public_order_token', $dto->publicToken)->firstOrFail();

        // 2. Lấy items active, eager-load itemGroup (tránh N+1)
        $items = $this->itemRepository->getActiveMenuByRestaurantId($restaurant->id);

        // 3. Gom nhóm theo danh mục và trả về
        return $this->menuGrouper->group($items);
    }
}
