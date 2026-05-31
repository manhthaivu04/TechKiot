<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusTransitioned;
use App\Listeners\DeductInventoryListener;
use App\Listeners\HandleOrderSourceStrategyListener;
use App\Listeners\NotifyKitchenListener;
use App\Listeners\NotifyKitchenStatusListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider — Đăng ký toàn bộ Event ↔ Listener mappings.
 *
 * ── Observer Pattern (Wiring) ────────────────────────────────────────────────
 * File này là "bảng điều khiển" trung tâm cho Observer Pattern.
 * Khi thêm Listener mới (VD: SendSmsListener) → chỉ thêm 1 dòng vào đây.
 * Không cần đụng vào PlaceOrderAction hay bất kỳ Action nào khác.
 *
 * ── Quy ước gắn nhãn gói ─────────────────────────────────────────────────────
 * Mỗi Listener được ghi chú [BASIC] / [PRO] / [PREMIUM] để dễ review
 * và biết được Listener nào cần uncomment khi mở rộng gói.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [

        // ── Khi có đơn hàng mới ──────────────────────────────────────────────
        OrderPlaced::class => [
            NotifyKitchenListener::class,              // [BASIC]   Báo màn hình KDS
            HandleOrderSourceStrategyListener::class,  // [BASIC]   Trigger QR/POS Strategy
            DeductInventoryListener::class,            // [PREMIUM] Trừ kho nguyên liệu (guard bên trong)

            // TODO [PRO]:     \App\Listeners\LockTableListener::class,
            // TODO [PREMIUM]: \App\Listeners\SendSmsConfirmationListener::class,
        ],

        // ── Khi khách gọi thêm món ──────────────────────────────────────────
        \App\Events\OrderItemsAdded::class => [
            // Re-use listener hoặc tạo listener mới (VD: NotifyKitchenAddedItemsListener)
            NotifyKitchenListener::class, 
        ],

        // ── Khi trạng thái đơn hàng thay đổi ────────────────────────────────
        OrderStatusTransitioned::class => [
            NotifyKitchenStatusListener::class,        // [BASIC]   Báo KDS chuyển trạng thái

            // TODO [PRO]:     \App\Listeners\UpdateTableStatusListener::class,
            // TODO [PREMIUM]: \App\Listeners\AdjustInventoryOnCancelListener::class,
        ],
    ];
}
