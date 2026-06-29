<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Log;

/**
 * TableOrderStatusService — đồng bộ trạng thái bàn theo vòng đời đơn hàng.
 *
 * - Có đơn mới gắn bàn → bàn chuyển sang occupied.
 * - Đơn chuyển paid (và không còn đơn active khác) → bàn về available.
 */
class TableOrderStatusService
{
    /** Trạng thái đơn được coi là "đang chiếm bàn". */
    private const ACTIVE_ORDER_STATUSES = [
        OrderStatus::Open,
        OrderStatus::Cooking,
        OrderStatus::Served,
    ];

    public function markOccupiedForOrder(Order $order): void
    {
        if (! $order->table_id) {
            return;
        }

        $table = RestaurantTable::query()->find($order->table_id);

        if (! $table) {
            return;
        }

        if (in_array($table->status, [TableStatus::Inactive, TableStatus::Occupied], true)) {
            return;
        }

        $table->update(['status' => TableStatus::Occupied]);

        Log::info("Bàn [{$table->name}] chuyển sang occupied do đơn [{$order->order_code}].");
    }

    public function releaseIfNoActiveOrders(Order $order): void
    {
        if (! $order->table_id) {
            return;
        }

        $table = RestaurantTable::query()->find($order->table_id);

        if (! $table) {
            return;
        }

        if ($this->hasActiveOrders($table->id, $order->id)) {
            return;
        }

        if ($table->status === TableStatus::Inactive) {
            return;
        }

        $table->update(['status' => TableStatus::Available]);

        Log::info("Bàn [{$table->name}] chuyển sang available — không còn đơn active.");
    }

    private function hasActiveOrders(string $tableId, ?string $excludeOrderId = null): bool
    {
        $query = Order::query()
            ->where('table_id', $tableId)
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, self::ACTIVE_ORDER_STATUSES));

        if ($excludeOrderId) {
            $query->where('id', '!=', $excludeOrderId);
        }

        return $query->exists();
    }
}
