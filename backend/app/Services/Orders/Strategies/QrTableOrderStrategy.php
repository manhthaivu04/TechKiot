<?php

namespace App\Services\Orders\Strategies;

use App\Contracts\Orders\OrderSourceStrategy;
use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Log;

/**
 * Strategy cho kênh QR bàn — Feature: TABLE_MANAGEMENT (Gói Pro).
 *
 * Khác với QrStaticOrderStrategy:
 * - Biết chính xác bàn nào (thông qua table_id trong DTO).
 * - Gán table_id vào Order sau khi tạo (đảm bảo phòng race condition: gắn sau khi đơn đã insert).
 * - Broadcast thông báo cho Thu ngân KÈM thông tin bàn.
 */
class QrTableOrderStrategy implements OrderSourceStrategy
{
    public function handle(Order $order, PlaceOrderDTO $dto): void
    {
        // 1. Gán table_id vào Order (đảm bảo gắn sau khi insert thành công)
        if ($dto->tableId) {
            $order->update(['table_id' => $dto->tableId]);

            Log::info("Order [{$order->order_code}] gắn vào bàn [{$dto->tableId}].");
        }

        // 2. Broadcast báo Thu ngân có đơn QR mới từ bàn
        broadcast(new \App\Events\Broadcasts\CashierNewQrOrderBroadcast($order->refresh()));
        Log::info("Đã gửi Broadcast NewQrOrder (QrTable) cho Thu ngân: restaurant.{$order->restaurant_id}.cashier");
    }
}
