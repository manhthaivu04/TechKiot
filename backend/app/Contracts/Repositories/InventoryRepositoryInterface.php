<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract cho Inventory Repository.
 *
 * Service chỉ phụ thuộc interface này (DIP).
 */
interface InventoryRepositoryInterface extends BaseRepositoryInterface
{
    /** Lấy tồn kho theo restaurant, eager load item + warehouse, có hỗ trợ phân trang và filter */
    public function getByRestaurantId(string $restaurantId, array $filters = []);

    /** Lấy tồn kho theo warehouse */
    public function getByWarehouseId(string $warehouseId): Collection;

    /**
     * Tìm hoặc tạo bản ghi tồn kho (upsert pattern).
     * Dùng khi ghi sổ kho: nếu chưa có dòng inventory thì tạo mới, có rồi thì trả về.
     */
    public function findOrCreateByWarehouseAndItem(
        string $restaurantId,
        string $warehouseId,
        string $itemId
    ): Model;

    /**
     * Tăng/giảm số lượng tồn kho (atomic increment/decrement).
     */
    public function adjustQuantity(string $warehouseId, string $itemId, float $delta): void;
}
