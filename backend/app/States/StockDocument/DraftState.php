<?php

namespace App\States\StockDocument;

use App\Enums\DocumentStatus;

/**
 * DraftState — Trạng thái Nháp.
 *
 * Cho phép: confirm() → CONFIRMED, cancel() → CANCELLED.
 * Đây là trạng thái ban đầu duy nhất cho phép thao tác.
 */
class DraftState extends DocumentState
{
    public function confirm(): void
    {
        $this->document->update(['status' => DocumentStatus::CONFIRMED]);
        $this->document->refresh();
    }

    public function cancel(): void
    {
        $this->document->update(['status' => DocumentStatus::CANCELLED]);
        $this->document->refresh();
    }

    public function label(): string
    {
        return 'Nháp';
    }

    public function getValue(): DocumentStatus
    {
        return DocumentStatus::DRAFT;
    }
}
