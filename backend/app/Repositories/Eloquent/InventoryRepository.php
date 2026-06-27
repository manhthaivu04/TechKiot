<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Concrete Eloquent implementation của InventoryRepositoryInterface.
 *
 * SRP: class này CHỈ truy vấn database — không chứa business logic.
 */
class InventoryRepository extends BaseEloquentRepository implements InventoryRepositoryInterface
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId, array $filters = [])
    {
        $query = $this->model
            ->where('restaurant_id', $restaurantId)
            ->with(['item', 'warehouse']);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('item', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest('updated_at')->paginate($filters['per_page'] ?? 15);
    }

    public function getByWarehouseId(string $warehouseId): Collection
    {
        return $this->model
            ->where('warehouse_id', $warehouseId)
            ->with('item')
            ->orderBy('created_at')
            ->get();
    }

    public function findOrCreateByWarehouseAndItem(
        string $restaurantId,
        string $warehouseId,
        string $itemId
    ): Model {
        return $this->model->firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id'      => $itemId,
            ],
            [
                'restaurant_id' => $restaurantId,
                'quantity'       => 0,
            ]
        );
    }

    public function adjustQuantity(string $warehouseId, string $itemId, float $delta): void
    {
        $this->model
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->increment('quantity', $delta);
    }
}
