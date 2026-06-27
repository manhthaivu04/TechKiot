<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\InventoryTransactionRepositoryInterface;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryTransactionRepository extends BaseEloquentRepository implements InventoryTransactionRepositoryInterface
{
    public function __construct(InventoryTransaction $model)
    {
        parent::__construct($model);
    }

    public function getTransactions(string $restaurantId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model
            ->where('restaurant_id', $restaurantId)
            ->with(['item', 'warehouse', 'createdBy']);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }
}
