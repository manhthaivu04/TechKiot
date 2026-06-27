<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryTransactionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách lịch sử biến động sổ kho có phân trang.
     *
     * @param string $restaurantId
     * @param array $filters (warehouse_id, item_id, per_page)
     * @return LengthAwarePaginator
     */
    public function getTransactions(string $restaurantId, array $filters = []): LengthAwarePaginator;
}
