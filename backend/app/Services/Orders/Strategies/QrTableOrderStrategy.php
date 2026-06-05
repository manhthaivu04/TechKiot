<?php

namespace App\Services\Orders\Strategies;

use App\Contracts\Orders\OrderSourceStrategy;
use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Strategy cho kênh QR Table — Feature: QR_TABLE_ORDER (Gói Pro).
 *
 * ╔══════════════════════════════════════════════════════╗
 * ║  PRO MODULE — Chưa implement, chỉ là skeleton       ║
 * ║  Tạo sẵn để chứng minh việc "cắm thêm" dễ thế nào  ║
 * ╚══════════════════════════════════════════════════════╝
 *
 * Khi gói Pro được bật:
 * 1. Uncomment case QrTable trong OrderStrategyResolver.
 * 2. Implement logic bên dưới.
 * 3. Không cần chạm vào OrderService hay OrderSourceStrategy interface.
 *
 * Khác với QrStatic: QrTable biết chính xác bàn nào ($dto->tableId),
 * nên có thể cập nhật trạng thái bàn (occupied) và gắn đơn vào bàn.
 */
class QrTableOrderStrategy implements OrderSourceStrategy
{
    public function handle(Order $order, PlaceOrderDTO $dto): void
    {
        Log::info("Order [{$order->order_code}] created via QR Table channel.", [
            'table_id' => $dto->tableId,
        ]);

        // TODO (Pro): Cập nhật trạng thái bàn sang 'occupied'
        // $table = Table::findOrFail($dto->tableId);
        // $table->update(['status' => TableStatus::Occupied]);

        // TODO (Pro): Gắn order vào session bàn hiện tại
        // $tableSession = TableSession::findOrCreateFor($dto->tableId);
        // $tableSession->orders()->attach($order->id);

        // TODO (Pro): Notify màn hình nhân viên phục vụ bàn đó
        // event(new NewTableOrderReceived($order, $table));
    }
}
