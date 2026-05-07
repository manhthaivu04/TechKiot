<?php

namespace App\Repositories\Eloquent;

use App\Models\ItemGroup;
use App\Contracts\Repositories\ItemGroupRepositoryInterface;

class ItemGroupRepository extends BaseEloquentRepository implements ItemGroupRepositoryInterface
{
    public function __construct(ItemGroup $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId)
    {
        return $this->model->where('restaurant_id', $restaurantId)
                           ->orderBy('display_order')
                           ->get();
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model->where('id', $id)
                           ->where('restaurant_id', $restaurantId)
                           ->firstOrFail();
    }
}
