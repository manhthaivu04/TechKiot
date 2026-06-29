<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\TableOrderStatusService;

/**
 * LockTableListener [PRO]
 *
 * Khi có đơn mới gắn bàn → đánh dấu bàn đang sử dụng (occupied).
 */
class LockTableListener
{
    public function __construct(
        private readonly TableOrderStatusService $tableOrderStatusService,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $this->tableOrderStatusService->markOccupiedForOrder($event->order);
    }
}
