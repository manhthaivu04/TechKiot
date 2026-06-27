<?php

namespace App\Listeners;

use App\Events\StockDocumentConfirmed;
use App\Strategies\StockMovement\StockMovementStrategyResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessStockMovementListener — Xử lý ghi sổ kho khi chứng từ được xác nhận.
 *
 * Flow:
 *   1. Eager load items để tránh N+1.
 *   2. Resolve Strategy từ document_type (Strategy Pattern).
 *   3. Wrap toàn bộ trong DB::transaction() (Unit of Work).
 *   4. Gọi strategy->process() → cập nhật inventory + ghi transaction log.
 *
 * Nếu bất kỳ dòng item nào lỗi → toàn bộ transaction rollback.
 */
class ProcessStockMovementListener
{
    public function __construct(
        protected StockMovementStrategyResolver $resolver
    ) {}

    public function handle(StockDocumentConfirmed $event): void
    {
        $document = $event->stockDocument;

        // 1. Eager load để tránh N+1
        $document->load('stockDocumentItems.item');

        // 2. Resolve Strategy từ document_type
        $strategy = $this->resolver->resolve($document->document_type);

        // 3. Unit of Work — đảm bảo atomicity
        DB::transaction(function () use ($strategy, $document) {
            $strategy->process($document);
        });

        Log::info("StockMovement processed", [
            'document_id'   => $document->id,
            'document_code' => $document->code,
            'document_type' => $document->document_type->value,
            'items_count'   => $document->stockDocumentItems->count(),
        ]);
    }
}
