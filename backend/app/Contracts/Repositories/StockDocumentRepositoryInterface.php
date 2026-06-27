<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho StockDocument Repository.
 *
 * Service chỉ phụ thuộc interface này (DIP).
 */
interface StockDocumentRepositoryInterface extends BaseRepositoryInterface
{
    /** Lấy danh sách chứng từ theo restaurant, eager load relations */
    public function getByRestaurantId(string $restaurantId): Collection;

    /** Sinh mã chứng từ tự động theo prefix + số thứ tự (VD: PN001, PX002) */
    public function generateCode(string $restaurantId, string $prefix): string;
}
