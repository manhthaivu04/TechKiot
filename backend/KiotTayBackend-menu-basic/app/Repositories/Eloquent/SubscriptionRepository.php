<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Models\RestaurantSubscription;
use Illuminate\Support\Collection;

class SubscriptionRepository extends BaseEloquentRepository implements SubscriptionRepositoryInterface
{
    public function __construct(RestaurantSubscription $model)
    {
        parent::__construct($model);
    }

    public function findActiveByRestaurant(string $restaurantId): ?RestaurantSubscription
    {
        return $this->model->newQuery()
            ->with('package.features')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->latest('activated_at')
            ->first();
    }

    public function findByRestaurant(string $restaurantId): Collection
    {
        return $this->model->newQuery()
            ->with('package')
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findExpiredActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->where('end_date', '<', now()->toDateString())
            ->get();
    }
}
