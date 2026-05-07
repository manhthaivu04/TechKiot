<?php

namespace App\Repositories\Eloquent;

use App\Models\Item;
use App\Contracts\Repositories\ItemRepositoryInterface;

class ItemRepository extends BaseEloquentRepository implements ItemRepositoryInterface
{
    public function __construct(Item $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId, array $filters = [])
    {
        $query = $this->model->where('restaurant_id', $restaurantId)->with('itemGroup');

        if (isset($filters['item_group_id'])) {
            $query->where('item_group_id', $filters['item_group_id']);
        }

        if (isset($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model->where('id', $id)
                           ->where('restaurant_id', $restaurantId)
                           ->firstOrFail();
    }
}
