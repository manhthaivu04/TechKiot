<?php

namespace App\States\StockDocument;

use App\Enums\DocumentStatus;

/**
 * CancelledState — Trạng thái Đã huỷ (terminal state).
 *
 * Không cho phép bất kỳ hành động nào — base class throw exception.
 */
class CancelledState extends DocumentState
{
    public function label(): string
    {
        return 'Đã huỷ';
    }

    public function getValue(): DocumentStatus
    {
        return DocumentStatus::CANCELLED;
    }
}
