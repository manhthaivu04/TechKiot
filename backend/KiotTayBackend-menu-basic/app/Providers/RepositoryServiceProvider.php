<?php

namespace App\Providers;

use App\Contracts\Repositories\FeatureRepositoryInterface;
use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Contracts\Repositories\RestaurantRepositoryInterface;
use App\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\FeatureServiceInterface;
use App\Contracts\Services\PackageServiceInterface;
use App\Contracts\Services\RestaurantServiceInterface;
use App\Contracts\Services\SubscriptionServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Repositories\Eloquent\FeatureRepository;
use App\Repositories\Eloquent\PackageRepository;
use App\Repositories\Eloquent\RestaurantRepository;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\FeatureService;
use App\Services\PackageService;
use App\Services\RestaurantService;
use App\Services\SubscriptionService;
use App\Services\UserService;
use App\Contracts\Repositories\ItemGroupRepositoryInterface;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Contracts\Services\ItemGroupServiceInterface;
use App\Contracts\Services\ItemServiceInterface;
use App\Repositories\Eloquent\ItemGroupRepository;
use App\Repositories\Eloquent\ItemRepository;
use App\Services\ItemGroupService;
use App\Services\ItemService;
use Illuminate\Support\ServiceProvider;

/**
 * RepositoryServiceProvider
 *
 * The ONLY place where Contracts are bound to concrete implementations.
 * Swapping the data layer (e.g. adding a Redis cache decorator) means
 * changing ONLY this file — satisfying the Open/Closed Principle at the
 * application wiring level.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repositories ───────────────────────────────────────────────────────
        $this->app->bind(RestaurantRepositoryInterface::class, RestaurantRepository::class);
        $this->app->bind(FeatureRepositoryInterface::class,    FeatureRepository::class);
        $this->app->bind(PackageRepositoryInterface::class,    PackageRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(UserRepositoryInterface::class,       UserRepository::class);
        $this->app->bind(ItemGroupRepositoryInterface::class, ItemGroupRepository::class);
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);

        // ── Services ───────────────────────────────────────────────────────────
        $this->app->bind(RestaurantServiceInterface::class, RestaurantService::class);
        $this->app->bind(FeatureServiceInterface::class,    FeatureService::class);
        $this->app->bind(PackageServiceInterface::class,    PackageService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(UserServiceInterface::class,       UserService::class);
        $this->app->bind(ItemGroupServiceInterface::class, ItemGroupService::class);
        $this->app->bind(ItemServiceInterface::class, ItemService::class);
    }
}
