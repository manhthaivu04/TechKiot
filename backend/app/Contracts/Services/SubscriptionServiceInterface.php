<?php

namespace App\Contracts\Services;

use App\Models\RestaurantSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubscriptionServiceInterface
{
    public function listForRestaurant(string $restaurantId): Collection;

    public function findOrFail(string $id): RestaurantSubscription;

    /**
     * Assign (or upgrade) a package to a restaurant.
     * Creates a new subscription record and activates it immediately.
     */
    public function assign(string $restaurantId, string $packageId, ?string $packagePriceId = null): RestaurantSubscription;

    /**
     * Cancel an active subscription.
     */
    public function cancel(string $subscriptionId): RestaurantSubscription;

    /**
     * Mark expired subscriptions – typically called by a scheduler.
     */
    public function expireOverdue(): int;

    public function activeForRestaurant(string $restaurantId): ?RestaurantSubscription;
}
