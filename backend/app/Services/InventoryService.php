<?php

namespace App\Services;

use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\InventoryTransactionRepositoryInterface;
use App\Contracts\Services\InventoryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        protected InventoryRepositoryInterface $inventoryRepository,
        protected InventoryTransactionRepositoryInterface $transactionRepository
    ) {}

    public function getInventory(string $restaurantId, array $filters = [])
    {
        return $this->inventoryRepository->getByRestaurantId($restaurantId, $filters);
    }

    public function getTransactions(string $restaurantId, array $filters = []): LengthAwarePaginator
    {
        return $this->transactionRepository->getTransactions($restaurantId, $filters);
    }
}
