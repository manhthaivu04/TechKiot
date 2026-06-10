<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TableAreaRepositoryInterface;
use App\Models\TableArea;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Eloquent implementation của TableAreaRepositoryInterface.
 *
 * SRP: class này CHỈ truy vấn database — không chứa business logic.
 * Mọi query đều scope theo restaurant_id để đảm bảo tenant isolation.
 */
class TableAreaRepository extends BaseEloquentRepository implements TableAreaRepositoryInterface
{
    public function __construct(TableArea $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId): Collection
    {
        return $this->model
            ->where('restaurant_id', $restaurantId)
            ->orderBy('display_order')
            ->orderBy('created_at')
            ->get();
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model
            ->where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();
    }
}
