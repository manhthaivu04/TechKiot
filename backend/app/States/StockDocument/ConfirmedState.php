<?php

namespace App\States\StockDocument;

use App\Enums\DocumentStatus;

/**
 * ConfirmedState — Trạng thái Đã xác nhận (terminal state).
 *
 * Không cho phép bất kỳ hành động nào — base class throw exception.
 */
class ConfirmedState extends DocumentState
{
    public function label(): string
    {
        return 'Đã xác nhận';
    }

    public function getValue(): DocumentStatus
    {
        return DocumentStatus::CONFIRMED;
    }
}
