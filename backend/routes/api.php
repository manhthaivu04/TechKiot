<?php

use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\OwnerUserController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Tenant\AnalyticsController;
use App\Http\Controllers\Api\Tenant\StaffController;
use App\Http\Controllers\Api\Tenant\TableAreaController;
use App\Http\Controllers\Api\Tenant\RestaurantTableController;
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

// ── Public: QR Menu (không cần đăng nhập — khách hàng quét QR) ─────────────
// Tương tự pattern Order: khách gọi endpoint này trước khi đặt món.
// public_token + type được nhúng vào QR Code bởi hệ thống khi tạo QR.
//
// Ví dụ QR tĩnh (Basic): GET /api/public/menu?public_token={restaurant_id}&type=qr_static
// Ví dụ QR bàn  (Pro):   GET /api/public/menu?public_token={table_id}&type=qr_table
Route::prefix('public')->name('public.')->group(function () {
    Route::get('menu', [\App\Http\Controllers\Api\Public\QrMenuController::class, 'index'])
        ->name('menu.index');
    Route::post('orders', [\App\Http\Controllers\Api\Public\PublicOrderController::class, 'store'])
        ->name('orders.store');
    Route::get('orders/{id}', [\App\Http\Controllers\Api\Public\PublicOrderController::class, 'show'])
        ->name('orders.show');
    Route::post('orders/{id}/items', [\App\Http\Controllers\Api\Public\PublicOrderController::class, 'addItems'])
        ->name('orders.items.store');
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
        Route::patch('restaurants/{id}/lock', [RestaurantController::class, 'lock'])->name('restaurants.lock');
        Route::patch('restaurants/{id}/unlock', [RestaurantController::class, 'unlock'])->name('restaurants.unlock');

        // Owner users (restaurant owners)
        Route::post('owners', [OwnerUserController::class, 'store'])->name('owners.store');

        // Subscriptions (nested under restaurants)
        Route::prefix('restaurants/{restaurantId}/subscriptions')->name('restaurants.subscriptions.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::get('/active', [SubscriptionController::class, 'active'])->name('active');
            Route::post('/', [SubscriptionController::class, 'assign'])->name('assign');
        });
        Route::patch('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Features (Platform-level feature toggle management)
        Route::apiResource('features', FeatureController::class)
            ->except(['destroy']);
        Route::patch('features/{id}/toggle', [FeatureController::class, 'toggle'])->name('features.toggle');

        // Packages (Service packages)
        Route::apiResource('packages', PackageController::class)
            ->except(['destroy']);
        Route::patch('packages/{id}/toggle', [PackageController::class, 'toggle'])->name('packages.toggle');
        Route::put('packages/{id}/features', [PackageController::class, 'syncFeatures'])->name('packages.features.sync');
    });

    // ── Tenant (Restaurant) ─────────────────────────────────────────────────
    // Tenant users (scoped by restaurant_id). Module-level routes apply their own role/feature guards.
    Route::middleware('role:OWNER,MANAGER,WAITER,KITCHEN,CASHIER')->prefix('tenant')->name('tenant.')->group(function () {

        // Basic Features
        // Basic Features
        Route::middleware(['feature:MENU_MANAGEMENT'])->group(function () {
            // Mọi nhân viên (Owner, Manager, Waiter, Cashier, Kitchen) đều có thể xem danh sách món ăn và danh mục để tạo đơn/hiển thị POS
            Route::get('item-groups', [\App\Http\Controllers\Api\Tenant\ItemGroupController::class, 'index']);
            Route::get('item-groups/{item_group}', [\App\Http\Controllers\Api\Tenant\ItemGroupController::class, 'show']);
            Route::get('items', [\App\Http\Controllers\Api\Tenant\ItemController::class, 'index']);
            Route::get('items/{item}', [\App\Http\Controllers\Api\Tenant\ItemController::class, 'show']);

            // Chỉ OWNER, MANAGER mới có quyền thêm/sửa/xoá món ăn và danh mục
            Route::middleware('role:OWNER,MANAGER')->group(function () {
                Route::apiResource('item-groups', \App\Http\Controllers\Api\Tenant\ItemGroupController::class)->except(['index', 'show']);
                Route::apiResource('items', \App\Http\Controllers\Api\Tenant\ItemController::class)->except(['index', 'show']);
                
                // BOM Endpoints
                Route::get('items/{item}/ingredients', [\App\Http\Controllers\Api\Tenant\ItemRecipeController::class, 'show']);
                Route::post('items/{item}/recipe', [\App\Http\Controllers\Api\Tenant\ItemRecipeController::class, 'sync']);
            });
        });

        // ── Orders (Basic: POS_QUICK_ORDER + QR_STATIC_ORDER) ─────────────────
        // Lý do không dùng apiResource: storePayment và updateStatus là custom routes.
        // POS_QUICK_ORDER là feature tối thiểu để vào module orders.
        Route::middleware('feature:POS_QUICK_ORDER')->group(function () {
            Route::get('menu', [\App\Http\Controllers\Api\Tenant\MenuController::class, 'index'])->name('menu.index');
            Route::get('orders', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{id}', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{id}/status', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{id}/items', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'addItems'])->name('orders.items.store');
            Route::patch('orders/{id}/items/{itemId}', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'updateItem'])->name('orders.items.update');
            Route::delete('orders/{id}/items/{itemId}', [\App\Http\Controllers\Api\Tenant\OrderController::class, 'removeItem'])->name('orders.items.destroy');
            // ── Payment routes (tách khỏi OrderController) ─────────────────────────
            Route::post('orders/{id}/payments', [\App\Http\Controllers\Api\Tenant\PaymentController::class, 'store'])->name('orders.payments.store');
            Route::get('orders/{id}/payments', [\App\Http\Controllers\Api\Tenant\PaymentController::class, 'index'])->name('orders.payments.index');
        });

        // Pro Features
        // Payment Method Settings (GET cho tất cả nhân viên để hiện phương thức lúc thanh toán)
        Route::get('payment-method-settings', [\App\Http\Controllers\Api\Tenant\PaymentMethodSettingController::class, 'index'])
            ->name('payment-method-settings.index');

        // ── Payment Method Settings (Cập nhật: OWNER, MANAGER) ──────────────────────────
        // Quản lý bật/tắt phương thức thanh toán — không cần feature guard vì
        // đây là cấu hình cơ bản của mọi nhà hàng.
        Route::middleware('role:OWNER,MANAGER')->group(function () {

            // ⚠️ Route tĩnh (transfer/qr) PHẢI đứng TRƯỚC route động ({method}/toggle)
            // để Laravel không nhầm "transfer" là tham số {method}
            Route::post('payment-method-settings/transfer/qr', [\App\Http\Controllers\Api\Tenant\PaymentMethodSettingController::class, 'uploadQr'])
                ->name('payment-method-settings.transfer.qr.upload');
            Route::delete('payment-method-settings/transfer/qr', [\App\Http\Controllers\Api\Tenant\PaymentMethodSettingController::class, 'deleteQr'])
                ->name('payment-method-settings.transfer.qr.delete');

            Route::patch('payment-method-settings/{method}/toggle', [\App\Http\Controllers\Api\Tenant\PaymentMethodSettingController::class, 'toggle'])
                ->name('payment-method-settings.toggle');
            Route::patch('payment-method-settings/{method}', [\App\Http\Controllers\Api\Tenant\PaymentMethodSettingController::class, 'update'])
                ->name('payment-method-settings.update');
        });

        // Pro Features — Table Management
        Route::middleware(['feature:TABLE_MANAGEMENT'])->group(function () {
            
            // 1. Nhóm chỉ XEM (Ai cũng vào được: Owner, Manager, Cashier, Waiter, Kitchen)
            // Lưu ý: Middleware tổng bên ngoài đã có 'role:OWNER,MANAGER,WAITER,KITCHEN,CASHIER'
            Route::get('table-areas', [\App\Http\Controllers\Api\Tenant\TableAreaController::class, 'index']);
            Route::get('table-areas/{id}', [\App\Http\Controllers\Api\Tenant\TableAreaController::class, 'show']);
            
            Route::get('restaurant-tables', [\App\Http\Controllers\Api\Tenant\RestaurantTableController::class, 'index']);
            Route::get('restaurant-tables/{id}', [\App\Http\Controllers\Api\Tenant\RestaurantTableController::class, 'show']);

            // 2. Nhóm THAO TÁC tạo/sửa/xóa (Chỉ dành cho Chủ quán và Quản lý)
            Route::middleware('role:OWNER,MANAGER')->group(function () {
                Route::apiResource('table-areas', \App\Http\Controllers\Api\Tenant\TableAreaController::class)->except(['index', 'show']);
                Route::apiResource('restaurant-tables', \App\Http\Controllers\Api\Tenant\RestaurantTableController::class)->except(['index', 'show']);
            });
        });

        Route::middleware(['feature:STAFF_MANAGEMENT', 'role:OWNER,MANAGER'])->group(function () {
            Route::apiResource('staff', StaffController::class);
        });

        // ── Analytics Dashboard (OWNER, MANAGER only) ────────────────────────
        Route::middleware('role:OWNER,MANAGER')->group(function () {
            Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard'])
                ->name('analytics.dashboard');
            Route::get('analytics/transactions', [AnalyticsController::class, 'transactions'])
                ->name('analytics.transactions');
        });

        // Premium Features
        Route::middleware('feature:INVENTORY_MANAGEMENT')->group(function () {
            Route::middleware('role:OWNER,MANAGER')->group(function () {
                Route::apiResource('warehouses', \App\Http\Controllers\Api\Tenant\WarehouseController::class)
                    ->only(['index', 'store', 'update', 'destroy']);

                // ── Stock Documents (Chứng từ kho) ─────────────────────────────
                // Không dùng apiResource vì confirm/cancel là custom actions.
                Route::get('stock-documents', [\App\Http\Controllers\Api\Tenant\StockDocumentController::class, 'index'])
                    ->name('stock-documents.index');
                Route::post('stock-documents', [\App\Http\Controllers\Api\Tenant\StockDocumentController::class, 'store'])
                    ->name('stock-documents.store');
                Route::patch('stock-documents/{id}/confirm', [\App\Http\Controllers\Api\Tenant\StockDocumentController::class, 'confirm'])
                    ->name('stock-documents.confirm');
                Route::patch('stock-documents/{id}/cancel', [\App\Http\Controllers\Api\Tenant\StockDocumentController::class, 'cancel'])
                    ->name('stock-documents.cancel');

                // ── Inventory Dashboard (Tồn kho & Lịch sử) ───────────────────
                Route::get('inventory', [\App\Http\Controllers\Api\Tenant\InventoryController::class, 'index'])
                    ->name('inventory.index');
                Route::get('inventory-transactions', [\App\Http\Controllers\Api\Tenant\InventoryController::class, 'transactions'])
                    ->name('inventory-transactions.index');
            });
        });
    });
});
