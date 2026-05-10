<?php

namespace App\Services\Orders\Strategies;

use App\Contracts\Orders\OrderSourceStrategy;
use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Strategy cho kênh POS (Cashier) — Feature: POS_QUICK_ORDER.
 *
 * Cashier trực tiếp tạo đơn tại quầy, không cần bước xử lý thêm.
 * Handle() trống là intentional — giữ interface nhất quán và mở rộng dễ dàng.
 * (e.g., sau này có thể in phiếu bếp tự động tại đây.)
 */
class CashierOrderStrategy implements OrderSourceStrategy
{
    public function handle(Order $order, PlaceOrderDTO $dto): void
    {
        // Kênh Cashier: nhân viên đã nhìn thấy đơn trực tiếp trên POS.
        // Không cần push notification hay side-effect nào thêm.
        Log::info("Order [{$order->order_code}] created via Cashier channel.");

        // TODO: Tích hợp máy in phiếu bếp tự động (thermal printer) tại đây
        // $this->printerService->printKitchenTicket($order);
    }
}
