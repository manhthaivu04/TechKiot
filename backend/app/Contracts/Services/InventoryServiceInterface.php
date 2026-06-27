<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryServiceInterface
{
    /**
     * Lấy tồn kho hiện tại.
     */
    public function getInventory(string $restaurantId, array $filters = []);

    /**
     * Lấy lịch sử biến động sổ kho.
     */
    public function getTransactions(string $restaurantId, array $filters = []): LengthAwarePaginator;
}
