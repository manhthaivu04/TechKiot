<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\WarehouseRepositoryInterface;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Eloquent implementation của WarehouseRepositoryInterface.
 *
 * SRP: class này CHỈ truy vấn database — không chứa business logic.
 * Mọi query đều scope theo restaurant_id để đảm bảo tenant isolation.
 */
class WarehouseRepository extends BaseEloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    public function findByRestaurant(string $restaurantId): Collection
    {
        return $this->model
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at')
            ->get();
    }

    public function unsetDefaultByRestaurant(string $restaurantId): void
    {
        $this->model
            ->where('restaurant_id', $restaurantId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
