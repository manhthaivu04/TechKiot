<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho Item Repository.
 *
 * Tầng Service/Strategy chỉ phụ thuộc interface này (DIP),
 * không biết Eloquent hay SQL tồn tại ở phía dưới.
 */
interface ItemRepositoryInterface extends BaseRepositoryInterface
{
    // ── Dành cho Owner/Admin ────────────────────────────────────────────────

    public function getByRestaurantId(string $restaurantId, array $filters = []);
    public function findByIdAndRestaurantId(string $id, string $restaurantId);

    // ── Dành cho luồng QR Menu (public) ─────────────────────────────────────

    /**
     * Lấy toàn bộ Items đang active (is_active = true, IN_STOCK)
     * của một nhà hàng, eager-load `itemGroup` để Strategy gom nhóm
     * mà không phát sinh thêm N+1 query.
     *
     * Trả Collection thay vì paginate vì client QR cần toàn bộ menu
     * để render offline / cache trên thiết bị di động.
     *
     * @param  string                         $restaurantId UUID nhà hàng
     * @return Collection<int, \App\Models\Item>
     */
    public function getActiveMenuByRestaurantId(string $restaurantId): Collection;
}
