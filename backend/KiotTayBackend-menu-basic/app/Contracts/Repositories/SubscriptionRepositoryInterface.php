<?php

namespace App\Contracts\Repositories;

use App\Models\RestaurantSubscription;
use Illuminate\Support\Collection;

interface SubscriptionRepositoryInterface extends BaseRepositoryInterface
{
    public function findActiveByRestaurant(string $restaurantId): ?RestaurantSubscription;

    /** @return Collection<int, RestaurantSubscription> */
    public function findByRestaurant(string $restaurantId): Collection;

    /** Return subscriptions whose end_date has passed and are still marked active */
    public function findExpiredActive(): Collection;
}
