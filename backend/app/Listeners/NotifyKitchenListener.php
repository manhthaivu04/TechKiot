<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

/**
 * NotifyKitchenListener [PRO]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Broadcast đơn hàng xuống màn hình Kitchen Display System (KDS) của bếp.
 * Bếp sẽ thấy đơn mới xuất hiện và biết cần chuẩn bị món gì.
 *
 * ── Gói áp dụng: PRO / PREMIUM ───────────────────────────────────────────────
 * Gói BASIC không có tài khoản nhân viên -> Không có App Bếp.
 * Sẽ dùng Guard clause để chặn tính năng này đối với gói BASIC.
 */
class NotifyKitchenListener
{
    public function handle(OrderPlaced|\App\Events\OrderItemsAdded $event): void
    {
        $order = $event->order->fresh(['items.item', 'restaurant']);
        $restaurant = $order->restaurant;

        // 1. LUÔN LUÔN BÁO CHO ĐIỆN THOẠI KHÁCH HÀNG (Dù là gói nào, nguồn nào)
        if ($event instanceof \App\Events\OrderItemsAdded) {
            broadcast(new \App\Events\Broadcasts\UIOrderItemsAddedBroadcast($event->order, $event->newItems));
            
            // Báo luôn cho Thu ngân để Thu ngân biết khách vừa gọi thêm món
            broadcast(new \App\Events\Broadcasts\CashierOrderItemsAddedBroadcast($event->order, $event->newItems));
        }

        // ── Guard 1: Chỉ chạy nếu nhà hàng có mua tính năng App Bếp (KDS) ──
        if (! $restaurant->hasFeature('KITCHEN_APP')) {
            return;
        }

        // ── Guard 2: Nếu đơn hàng từ khách tự quét QR -> Không báo bếp vội ──
        if (in_array($order->source_channel, [\App\Enums\OrderSourceChannel::QrStatic, \App\Enums\OrderSourceChannel::QrTable])) {
            return;
        }

        // 2. KÍCH HOẠT BROADCAST CHO BẾP (Sau khi đã vượt qua các trạm gác)
        Log::info("[KDS] Đơn hàng mới tới bếp.", ['order_code' => $order->order_code]);

        if ($event instanceof OrderPlaced) {
            broadcast(new \App\Events\Broadcasts\KitchenOrderPlacedBroadcast($order, $event->dto));
        } elseif ($event instanceof \App\Events\OrderItemsAdded) {
            broadcast(new \App\Events\Broadcasts\KitchenOrderItemsAddedBroadcast($order, $event->newItems));
        }
    }
}
