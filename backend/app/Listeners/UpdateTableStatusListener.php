<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusTransitioned;
use App\Services\TableOrderStatusService;

/**
 * UpdateTableStatusListener [PRO]
 *
 * Giải phóng bàn khi đơn chuyển sang paid hoặc cancelled.
 * Không đổi trạng thái bàn ở các bước trung gian (cooking, served).
 * Logic an toàn: `releaseIfNoActiveOrders` chỉ release khi bàn
 * không còn đơn active nào khác — phòng trường hợp merge bàn sau này.
 */
class UpdateTableStatusListener
{
    public function __construct(
        private readonly TableOrderStatusService $tableOrderStatusService,
    ) {}

    public function handle(OrderStatusTransitioned $event): void
    {
        $shouldRelease = in_array($event->to, [
            OrderStatus::Paid,
            OrderStatus::Cancelled,
        ], true);

        if (! $shouldRelease) {
            return;
        }

        $this->tableOrderStatusService->releaseIfNoActiveOrders($event->order);
    }
}
