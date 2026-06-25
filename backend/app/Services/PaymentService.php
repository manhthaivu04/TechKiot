<?php

namespace App\Services;

use App\Contracts\Services\PaymentServiceInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\ProcessPaymentAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * PaymentService — Thin Facade cho module Payment.
 *
 * ── Vai trò ───────────────────────────────────────────────────────────────────
 * Đây là Single Entry Point duy nhất mà bên ngoài (Controller, Job, v.v.)
 * tương tác với hệ thống thanh toán. Không chứa business logic.
 *
 * ── Tại sao tách khỏi OrderService? ──────────────────────────────────────────
 * - SRP: Order lo vòng đời món ăn/bàn; Payment lo dòng tiền.
 * - Mở rộng: Khi thêm Subscription Payment, module Payment đã sẵn sàng tái dùng.
 * - Testability: Có thể mock PaymentService độc lập khi test OrderController.
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly ProcessPaymentAction $processPaymentAction,
    ) {}

    public function record(Order $order, RecordPaymentDTO $dto): Payment
    {
        return $this->processPaymentAction->execute($order, $dto);
    }

    public function listByOrder(Order $order): Collection
    {
        return $order->payments()->latest()->get();
    }
}
