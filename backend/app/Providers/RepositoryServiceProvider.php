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
use App\Contracts\Services\StaffServiceInterface;
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
use App\Services\StaffService;
use App\Contracts\Repositories\ItemGroupRepositoryInterface;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Contracts\Repositories\TableAreaRepositoryInterface;
use App\Contracts\Repositories\RecipeRepositoryInterface;
use App\Contracts\Repositories\RestaurantTableRepositoryInterface;
use App\Contracts\Repositories\WarehouseRepositoryInterface;
use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\InventoryTransactionRepositoryInterface;
use App\Contracts\Repositories\StockDocumentRepositoryInterface;
use App\Contracts\Services\ItemGroupServiceInterface;
use App\Contracts\Services\ItemServiceInterface;
use App\Contracts\Services\OrderServiceInterface;
use App\Contracts\Services\TableAreaServiceInterface;
use App\Contracts\Services\RestaurantTableServiceInterface;
use App\Contracts\Services\WarehouseServiceInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Contracts\Services\StockDocumentServiceInterface;
use App\Repositories\Eloquent\ItemGroupRepository;
use App\Repositories\Eloquent\ItemRepository;
use App\Repositories\Eloquent\TableAreaRepository;
use App\Repositories\Eloquent\RecipeRepository;
use App\Repositories\Eloquent\RestaurantTableRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Eloquent\InventoryRepository;
use App\Repositories\Eloquent\InventoryTransactionRepository;
use App\Repositories\Eloquent\StockDocumentRepository;
use App\Services\ItemGroupService;
use App\Services\ItemService;
use App\Services\OrderService;
use App\Services\TableAreaService;
use App\Services\RestaurantTableService;
use App\Services\WarehouseService;
use App\Services\InventoryService;
use App\Services\StockDocumentService;
use App\Services\Orders\Actions\AddItemsToOrderAction;
use App\Services\Orders\Actions\PlaceOrderAction;
use App\Services\Orders\Actions\RecordPaymentAction;
use App\Services\Orders\Actions\TransitionOrderAction;
// ── Menu Module ────────────────────────────────────────────────────────────
use App\Contracts\Menu\MenuSourceStrategy;
use App\Services\Menu\MenuGrouper;
use App\Services\Menu\MenuService;
use App\Services\Menu\MenuStrategyResolver;
use App\Services\Menu\Strategies\QrStaticMenuStrategy;
// ── Payment Module ──────────────────────────────────────────────────────────
use App\Contracts\Services\PaymentServiceInterface;
use App\Services\PaymentService;
use App\Services\Payments\ProcessPaymentAction;
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
        $this->app->bind(TableAreaRepositoryInterface::class, TableAreaRepository::class);
        $this->app->bind(RestaurantTableRepositoryInterface::class, RestaurantTableRepository::class);
        $this->app->bind(RecipeRepositoryInterface::class, RecipeRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(InventoryTransactionRepositoryInterface::class, InventoryTransactionRepository::class);
        $this->app->bind(StockDocumentRepositoryInterface::class, StockDocumentRepository::class);

        // ── Services ───────────────────────────────────────────────────────────
        $this->app->bind(RestaurantServiceInterface::class, RestaurantService::class);
        $this->app->bind(FeatureServiceInterface::class,    FeatureService::class);
        $this->app->bind(PackageServiceInterface::class,    PackageService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(UserServiceInterface::class,       UserService::class);
        $this->app->bind(StaffServiceInterface::class,      StaffService::class);
        $this->app->bind(ItemGroupServiceInterface::class, ItemGroupService::class);
        $this->app->bind(ItemServiceInterface::class, ItemService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(TableAreaServiceInterface::class, TableAreaService::class);
        $this->app->bind(RestaurantTableServiceInterface::class, RestaurantTableService::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
        $this->app->bind(WarehouseServiceInterface::class, WarehouseService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(StockDocumentServiceInterface::class, StockDocumentService::class);

        // ── Order Action Classes ────────────────────────────────────────────
        // Stateless — an toàn dùng singleton để tái sử dụng qua nhiều requests.
        // Controller có thể inject thẳng Action thay vì qua OrderService nếu muốn.
        $this->app->singleton(PlaceOrderAction::class);
        $this->app->singleton(AddItemsToOrderAction::class);
        $this->app->singleton(RecordPaymentAction::class);
        $this->app->singleton(TransitionOrderAction::class);

        // ── Payment Action ──────────────────────────────────────────────────
        $this->app->singleton(ProcessPaymentAction::class);

        // ── Menu Module ────────────────────────────────────────────────────
        // MenuService và Resolver được đăng ký là singleton vì không có state
        // (giống OrderService — stateless, safe to share across requests).
        $this->app->singleton(MenuStrategyResolver::class);
        $this->app->singleton(MenuGrouper::class);
        $this->app->singleton(MenuService::class);

        // Các Strategy được tạo mới mỗi lần (app() trong Resolver lo việc này)
        $this->app->bind(QrStaticMenuStrategy::class);
    }
}
