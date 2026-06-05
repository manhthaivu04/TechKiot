<?php

namespace App\Services\Orders\Strategies;

use App\Contracts\Orders\OrderSourceStrategy;
use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Strategy cho kênh QR Code tĩnh — Feature: QR_STATIC_ORDER.
 *
 * Khi khách quét QR đặt món, cần notify nhân viên có đơn mới đến.
 * Đây là nơi đặt logic push notification / websocket event.
 *
 * Khác với QrTableStrategy (Pro): QR Static không biết bàn số mấy,
 * chỉ biết nhà hàng (thông qua public_order_token).
 */
class QrStaticOrderStrategy implements OrderSourceStrategy
{
    public function handle(Order $order, PlaceOrderDTO $dto): void
    {
        Log::info("Order [{$order->order_code}] created via QR Static channel.", [
            'customer_reference' => $dto->customerReference,
            'customer_name'      => $dto->customerName,
        ]);

        // TODO: Broadcast event để nhân viên nhận thông báo real-time
        // event(new NewQrOrderReceived($order));

        // TODO: Gửi SMS/Zalo OA xác nhận đơn cho khách (nếu có số điện thoại)
        // if ($dto->customerPhone) {
        //     $this->smsService->sendOrderConfirmation($dto->customerPhone, $order);
        // }
    }
}
