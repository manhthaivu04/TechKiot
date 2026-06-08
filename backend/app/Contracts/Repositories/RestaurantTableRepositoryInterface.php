<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho RestaurantTable Repository.
 *
 * Mọi query đều scope theo restaurant_id để đảm bảo tenant isolation.
 * Sau này có thể mở rộng thêm method cho QR, sơ đồ bàn (OCP).
 */
interface RestaurantTableRepositoryInterface extends BaseRepositoryInterface
{
    /** Lấy danh sách bàn theo restaurant, hỗ trợ filter area_id, status, search */
    public function getByRestaurantId(string $restaurantId, array $filters = []);

    /** Tìm bàn theo id + restaurant_id (tenant isolation) */
    public function findByIdAndRestaurantId(string $id, string $restaurantId);

    /** Lấy uid cuối cùng theo pattern B-xxx để tự sinh uid tiếp theo */
    public function getLastUid(string $restaurantId): ?string;

    /** Kiểm tra uid đã tồn tại trong nhà hàng chưa */
    public function uidExists(string $restaurantId, string $uid, ?string $excludeId = null): bool;
}
