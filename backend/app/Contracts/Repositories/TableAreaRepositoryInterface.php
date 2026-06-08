<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho TableArea Repository.
 *
 * Service chỉ phụ thuộc interface này (DIP),
 * không biết Eloquent hay bất kỳ ORM nào tồn tại ở phía dưới.
 */
interface TableAreaRepositoryInterface extends BaseRepositoryInterface
{
    /** Lấy danh sách khu vực bàn theo restaurant, sắp xếp display_order rồi created_at */
    public function getByRestaurantId(string $restaurantId): Collection;

    /** Tìm khu vực theo id + restaurant_id (tenant isolation) */
    public function findByIdAndRestaurantId(string $id, string $restaurantId);
}
