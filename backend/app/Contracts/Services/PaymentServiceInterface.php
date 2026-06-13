<?php

namespace App\Contracts\Services;

use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;
use App\Models\Payment;

/**
 * PaymentServiceInterface — Contract cho PaymentService.
 *
 * Tách hoàn toàn khỏi OrderServiceInterface.
 * Mọi nghiệp vụ "dòng tiền" đều đi qua đây, không qua OrderService.
 */
interface PaymentServiceInterface
{
    /**
     * Ghi nhận một lần thanh toán cho đơn hàng.
     * Hỗ trợ split payment: gọi nhiều lần cho cùng 1 Order.
     *
     * @throws \DomainException Nếu vi phạm business rule
     */
    public function record(Order $order, RecordPaymentDTO $dto): Payment;

    /**
     * Lấy toàn bộ lịch sử thanh toán của một đơn hàng.
     *
     * @return \Illuminate\Database\Eloquent\Collection<\App\Models\Payment>
     */
    public function listByOrder(Order $order): \Illuminate\Database\Eloquent\Collection;
}
