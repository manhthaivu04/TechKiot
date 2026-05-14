<?php

namespace App\Services\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * TransitionOrderAction — Chuyển trạng thái đơn hàng.
 *
 * ── Single Responsibility ────────────────────────────────────────────────────
 * Class này chỉ làm DUY NHẤT một việc: validate và thực hiện chuyển
 * trạng thái đơn hàng. Business rule của transition được đặt tập trung
 * tại Order::transitionTo() để đảm bảo tính nhất quán dù gọi từ đâu.
 *
 * Ví dụ sử dụng:
 * - Nhân viên bếp đánh dấu đơn "Đang nấu" (Processing).
 * - Thu ngân xác nhận đơn đã phục vụ xong (Served).
 * - Manager hủy đơn (Cancelled).
 */
class TransitionOrderAction
{
    /**
     * Thực thi chuyển trạng thái đơn hàng.
     *
     * @throws \DomainException Nếu transition không hợp lệ theo business rules
     */
    public function execute(Order $order, OrderStatus $newStatus): Order
    {
        // Delegate xuống Model — business rule transition nằm tập trung tại đây,
        // không rải ở Service hay Controller.
        $order->transitionTo($newStatus);

        return $order->refresh();
    }
}
