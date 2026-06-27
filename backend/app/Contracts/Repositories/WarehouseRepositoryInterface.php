<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho Warehouse Repository.
 *
 * Service chỉ phụ thuộc interface này (DIP),
 * không biết Eloquent hay bất kỳ ORM nào tồn tại ở phía dưới.
 */
interface WarehouseRepositoryInterface extends BaseRepositoryInterface
{
    /** Lấy danh sách kho theo restaurant, sắp xếp theo created_at */
    public function findByRestaurant(string $restaurantId): Collection;

    /** Unset tất cả kho default của restaurant về false */
    public function unsetDefaultByRestaurant(string $restaurantId): void;
}
