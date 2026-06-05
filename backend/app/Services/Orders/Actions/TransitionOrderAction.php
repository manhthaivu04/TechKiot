<?php

namespace App\Services\Orders\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderStatusTransitioned;
use App\Models\Order;

/**
 * TransitionOrderAction — Chuyển trạng thái đơn hàng.
 *
 * ── Sau khi tích hợp Observer Pattern ────────────────────────────────────────
 * Bếp nhấn nút "Đã nấu xong" → OrderController gọi Action này.
 * Action delegate xuống State Pattern để validate transition, sau đó
 * fire Event → Listener xử lý các side-effects (báo KDS, update bàn...).
 *
 * Luồng:
 *   TransitionOrderAction → order->transitionTo() [State validates]
 *                         → fire(OrderStatusTransitioned)
 *                         → NotifyKitchenStatusListener [báo màn bếp]
 *                         → (PRO) NotifyTableStatusListener [cập nhật bàn]
 */
class TransitionOrderAction
{
    /**
     * @throws \DomainException Nếu transition không hợp lệ theo State Pattern
     */
    public function execute(Order $order, OrderStatus $newStatus): Order
    {
        $fromStatus = $order->status;

        // State Pattern: kiểm tra và thực thi chuyển trạng thái
        $order->transitionTo($newStatus);

        // Observer Pattern: fire event để Listener xử lý side-effects
        OrderStatusTransitioned::dispatch(
            order: $order->refresh(),
            from:  $fromStatus,
            to:    $newStatus,
        );

        return $order;
    }
}
