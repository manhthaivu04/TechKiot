<?php

use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\OwnerUserController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Tenant\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — KiotTay SaaS Backend
|--------------------------------------------------------------------------
|
| All routes use the "api" prefix (configured in bootstrap/app.php).
| Auth is handled via Laravel Sanctum (stateless token).
|
*/

// ── Public ─────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// ── Authenticated ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth utilities
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });

    // ── Super Admin only ────────────────────────────────────────────────────
    Route::middleware('role:SUPER_ADMIN')->prefix('admin')->name('admin.')->group(function () {

        // Restaurants (Tenants)
        Route::post('restaurants/onboard', [RestaurantController::class, 'onboard'])->name('restaurants.onboard');
        Route::apiResource('restaurants', RestaurantController::class)
            ->except(['destroy']);
        Route::patch('restaurants/{id}/lock',   [RestaurantController::class, 'lock'])->name('restaurants.lock');
        Route::patch('restaurants/{id}/unlock', [RestaurantController::class, 'unlock'])->name('restaurants.unlock');

        // Owner users (restaurant owners)
        Route::post('owners', [OwnerUserController::class, 'store'])->name('owners.store');

        // Subscriptions (nested under restaurants)
        Route::prefix('restaurants/{restaurantId}/subscriptions')->name('restaurants.subscriptions.')->group(function () {
            Route::get('/',          [SubscriptionController::class, 'index'])->name('index');
            Route::get('/active',    [SubscriptionController::class, 'active'])->name('active');
            Route::post('/',         [SubscriptionController::class, 'assign'])->name('assign');
        });
        Route::patch('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Features (Platform-level feature toggle management)
        Route::apiResource('features', FeatureController::class)
            ->except(['destroy']);
        Route::patch('features/{id}/toggle', [FeatureController::class, 'toggle'])->name('features.toggle');

        // Packages (Service packages)
        Route::apiResource('packages', PackageController::class)
            ->except(['destroy']);
        Route::patch('packages/{id}/toggle',      [PackageController::class, 'toggle'])->name('packages.toggle');
        Route::put('packages/{id}/features',      [PackageController::class, 'syncFeatures'])->name('packages.features.sync');
    });

    // ── Tenant (Restaurant) ─────────────────────────────────────────────────
    // Tenant users (scoped by restaurant_id). Module-level routes apply their own role/feature guards.
    Route::middleware('role:OWNER,MANAGER,WAITER,KITCHEN,CASHIER')->prefix('tenant')->name('tenant.')->group(function () {

        // Basic Features
        Route::middleware(['feature:MENU_MANAGEMENT', 'role:OWNER,MANAGER'])->group(function () {
            Route::apiResource('item-groups', \App\Http\Controllers\Api\Tenant\ItemGroupController::class);
            Route::apiResource('items', \App\Http\Controllers\Api\Tenant\ItemController::class);
        });

        // ── Orders (Basic: POS_QUICK_ORDER + QR_STATIC_ORDER) ─────────────────
        // Lý do không dùng apiResource: storePayment và updateStatus là custom routes.
        // POS_QUICK_ORDER là feature tối thiểu để vào module orders.
        Route::middleware('feature:POS_QUICK_ORDER')->group(function () {
            Route::get('orders',                     [\App\Http\Controllers\Api\Tenant\OrderController::class, 'index'])->name('orders.index');
            Route::post('orders',                    [\App\Http\Controllers\Api\Tenant\OrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{id}',                [\App\Http\Controllers\Api\Tenant\OrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{id}/status',       [\App\Http\Controllers\Api\Tenant\OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{id}/payments',      [\App\Http\Controllers\Api\Tenant\OrderController::class, 'storePayment'])->name('orders.payments.store');
        });

        Route::middleware('feature:POS_QUICK_ORDER')->group(function () {
            // Route::post('orders/quick', [OrderController::class, 'quickOrder']);
        });

        // Pro Features
        Route::middleware('feature:TABLE_MANAGEMENT')->group(function () {
            // Route::apiResource('tables', TableController::class);
        });

        Route::middleware(['feature:STAFF_MANAGEMENT', 'role:OWNER,MANAGER'])->group(function () {
            Route::apiResource('staff', StaffController::class);
        });

        // Premium Features
        Route::middleware('feature:INVENTORY_MANAGEMENT')->group(function () {
            // Route::apiResource('inventory', InventoryController::class);
        });
    });
});
