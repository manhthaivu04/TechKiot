<?php

namespace App\Services;

use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Contracts\Repositories\RestaurantRepositoryInterface;
use App\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Contracts\Services\SubscriptionServiceInterface;
use App\Enums\SubscriptionStatus;
use App\Models\RestaurantSubscription;
use App\Models\PackagePrice;
use App\Strategies\Subscription\SubscriptionStrategyContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $repository,
        private readonly RestaurantRepositoryInterface   $restaurantRepository,
        private readonly PackageRepositoryInterface      $packageRepository,
    ) {}

    public function listForRestaurant(string $restaurantId): Collection
    {
        return $this->repository->findByRestaurant($restaurantId);
    }

    public function findOrFail(string $id): RestaurantSubscription
    {
        /** @var RestaurantSubscription */
        return $this->repository->findByIdOrFail($id);
    }

    public function activeForRestaurant(string $restaurantId): ?RestaurantSubscription
    {
        return $this->repository->findActiveByRestaurant($restaurantId);
    }

    /**
     * Assign a package to a restaurant.
     *
     * Business rules:
     * - The restaurant must exist and not be suspended.
     * - If an active subscription exists, cancel it first (upgrade flow).
     * - New subscription starts today and runs for package.duration_days or packagePrice.duration_days.
     */
    public function assign(string $restaurantId, string $packageId, ?string $packagePriceId = null): RestaurantSubscription
    {
        $restaurant = $this->restaurantRepository->findByIdOrFail($restaurantId);

        if (! $restaurant->isAccessible()) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Cannot assign a package to a suspended restaurant.',
            ]);
        }

        $package = $this->packageRepository->findByIdOrFail($packageId);

        if (! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => 'The selected package is no longer available.',
            ]);
        }

        $packagePrice = null;
        if ($packagePriceId) {
            $packagePrice = PackagePrice::find($packagePriceId);
            if (! $packagePrice || $packagePrice->package_id !== $package->id) {
                throw ValidationException::withMessages([
                    'package_price_id' => 'Invalid package price selected.',
                ]);
            }
        }

        // Cancel existing active subscription
        $existing = $this->repository->findActiveByRestaurant($restaurantId);
        if ($existing) {
            $this->repository->update($existing, [
                'status'       => SubscriptionStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);
        }

        $startDate = Carbon::today();
        $context   = new SubscriptionStrategyContext($package, $packagePrice);
        $endDate   = $context->getCalculatedEndDate($startDate, $packagePrice);

        /** @var RestaurantSubscription */
        return $this->repository->create([
            'restaurant_id'    => $restaurantId,
            'package_id'       => $packageId,
            'package_price_id' => $packagePriceId,
            'start_date'       => $startDate->toDateString(),
            'end_date'         => $endDate->toDateString(),
            'status'           => SubscriptionStatus::ACTIVE->value,
            'activated_at'     => now(),
        ]);
    }

    public function cancel(string $subscriptionId): RestaurantSubscription
    {
        $subscription = $this->findOrFail($subscriptionId);

        if (! $subscription->isActive()) {
            throw ValidationException::withMessages([
                'subscription' => 'Only active subscriptions can be cancelled.',
            ]);
        }

        /** @var RestaurantSubscription */
        return $this->repository->update($subscription, [
            'status'       => SubscriptionStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Expire all overdue active subscriptions.
     * Designed to be called from a scheduler (e.g. daily Artisan command).
     *
     * @return int Number of subscriptions expired
     */
    public function expireOverdue(): int
    {
        $overdue = $this->repository->findExpiredActive();
        $count   = 0;

        foreach ($overdue as $subscription) {
            $this->repository->update($subscription, [
                'status' => SubscriptionStatus::EXPIRED->value,
            ]);
            ++$count;
        }

        return $count;
    }
}
