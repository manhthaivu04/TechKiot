<?php

namespace App\Events;

use App\Models\StockDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StockDocumentConfirmed — Event phát ra khi chứng từ kho được xác nhận.
 *
 * Trigger: StockDocumentService::confirm() → sau khi State chuyển sang CONFIRMED.
 * Listener: ProcessStockMovementListener → xử lý ghi sổ kho (Strategy Pattern).
 *
 * Observer Pattern: Service chỉ cần dispatch event,
 * không cần biết có bao nhiêu Listener đang lắng nghe.
 */
class StockDocumentConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StockDocument $stockDocument
    ) {}
}
