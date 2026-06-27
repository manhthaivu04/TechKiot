<?php

namespace App\Contracts\Services;

use App\DTOs\WarehouseDTO;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho Warehouse Service.
 *
 * Controller chỉ inject interface này (DIP), không biết implementation cụ thể.
 */
interface WarehouseServiceInterface
{
    /** Lấy danh sách kho của nhà hàng */
    public function list(string $restaurantId): Collection;

    /** Tạo kho mới. Nếu is_default = true, unset các kho cũ trước */
    public function store(string $restaurantId, WarehouseDTO $dto);

    /** Cập nhật kho. Logic is_default tương tự store */
    public function update(string $warehouseId, WarehouseDTO $dto);

    /** Xóa kho. Không được xóa kho default */
    public function destroy(string $warehouseId): bool;
}
