<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusTransitioned;
use App\Events\RecipeUpdated;
use App\Events\StockDocumentConfirmed;
use App\Listeners\DeductInventoryListener;
use App\Listeners\HandleOrderSourceStrategyListener;
use App\Listeners\NotifyKitchenListener;
use App\Listeners\NotifyKitchenStatusListener;
use App\Listeners\AdjustInventoryOnOrderChangesListener;
use App\Listeners\ProcessStockMovementListener;
use App\Listeners\RecalculateItemCostPrice;
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
     * ── QUAN TRỌNG (Laravel 12 double-fire fix) ──────────────────────────────
     *
     * Laravel 12 mặc định auto-discover listeners bằng cách quét app/Listeners.
     * Khi App\Providers\EventServiceProvider extends base class, framework CÓ THỂ
     * đăng ký thêm một instance của base class (qua ApplicationBuilder::withEvents),
     * dẫn đến listener bị đăng ký 2 lần: 1 từ $listen, 1 từ auto-discovery.
     *
     * Fix: Tắt auto-discovery ở cả static property LẪN override method.
     */
    protected static $shouldDiscoverEvents = false;

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

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
            NotifyKitchenListener::class,
        ],

        // ── Khi món bị hủy/xóa khỏi đơn ─────────────────────────────────────
        \App\Events\OrderItemRemoved::class => [
            AdjustInventoryOnOrderChangesListener::class,
        ],

        // ── Khi thay đổi số lượng món ────────────────────────────────────────
        \App\Events\OrderItemUpdated::class => [
            AdjustInventoryOnOrderChangesListener::class,
        ],

        // ── Khi trạng thái đơn hàng thay đổi ────────────────────────────────
        OrderStatusTransitioned::class => [
            NotifyKitchenStatusListener::class,        // [BASIC]   Báo KDS chuyển trạng thái

            // TODO [PRO]:     \App\Listeners\UpdateTableStatusListener::class,
            AdjustInventoryOnOrderChangesListener::class, // [PREMIUM] Điều chỉnh kho khi hủy đơn
        ],

        // ── Khi công thức (BOM) được cập nhật ─────────────────────────────────
        RecipeUpdated::class => [
            RecalculateItemCostPrice::class,             // [PREMIUM] Tính lại cost_price
        ],

        // ── Khi có thanh toán được ghi nhận ──────────────────────────────────
        \App\Events\PaymentRecorded::class => [
            \App\Listeners\PrintReceiptListener::class, // [BASIC]   Log/in hóa đơn

            // TODO [PRO]:     \App\Listeners\NotifyCustomerListener::class,
            // TODO [PREMIUM]: \App\Listeners\SendReceiptEmailListener::class,
        ],

        // ── Khi chứng từ kho được xác nhận ──────────────────────────────────
        StockDocumentConfirmed::class => [
            ProcessStockMovementListener::class,        // [PREMIUM] Ghi sổ kho (Strategy Pattern)
        ],
    ];
}
