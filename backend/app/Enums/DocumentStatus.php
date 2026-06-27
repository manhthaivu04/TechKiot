<?php

namespace App\Enums;

/**
 * DocumentStatus — Trạng thái vòng đời chứng từ kho.
 *
 * Sơ đồ chuyển trạng thái:
 *   draft ──► confirmed
 *     │
 *     └──► cancelled
 *
 * Confirmed và Cancelled là trạng thái cuối (terminal state).
 */
enum DocumentStatus: string
{
    case DRAFT     = 'draft';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Nháp',
            self::CONFIRMED => 'Đã xác nhận',
            self::CANCELLED => 'Đã huỷ',
        };
    }
}
