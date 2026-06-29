<?php

namespace App\Services\Orders\Strategies;

use App\Contracts\Orders\OrderSourceStrategy;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\Actions\TransitionOrderAction;
use Illuminate\Support\Facades\Log;

/**
 * Strategy cho kênh POS (Cashier) — Feature: POS_QUICK_ORDER.
 *
 * Nhân viên/thu ngân tạo đơn → tự động chuyển sang cooking (xuống bếp),
 * không cần bước xác nhận thủ công như đơn QR từ khách.
 */
class CashierOrderStrategy implements OrderSourceStrategy
{
    public function __construct(
        private readonly TransitionOrderAction $transitionOrderAction,
    ) {}

    public function handle(Order $order, PlaceOrderDTO $dto): void
    {
        if ($order->status !== OrderStatus::Open) {
            return;
        }

        $this->transitionOrderAction->execute($order, OrderStatus::Cooking);

        Log::info("Order [{$order->order_code}] auto-sent to kitchen (cashier channel).");
    }
}
