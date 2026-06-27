<?php

namespace App\Enums;

/**
 * DocumentType — Loại chứng từ kho.
 *
 * RECEIPT:    Phiếu nhập kho (mua hàng, nhận hàng từ NCC).
 * ISSUE:      Phiếu xuất kho (xuất cho bếp, quầy bar...).
 * ADJUSTMENT: Phiếu điều chỉnh (kiểm kê, chênh lệch).
 * WASTE:      Phiếu hao hụt (hư hỏng, hết hạn).
 * RETURN:     Phiếu trả hàng (trả lại NCC).
 */
enum DocumentType: string
{
    case RECEIPT    = 'receipt';
    case ISSUE      = 'issue';
    case ADJUSTMENT = 'adjustment';
    case WASTE      = 'waste';
    case RETURN     = 'return';

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT    => 'Phiếu nhập kho',
            self::ISSUE      => 'Phiếu xuất kho',
            self::ADJUSTMENT => 'Phiếu điều chỉnh',
            self::WASTE      => 'Phiếu hao hụt',
            self::RETURN     => 'Phiếu trả hàng',
        };
    }

    /**
     * Prefix mã chứng từ theo loại.
     * VD: RECEIPT → PN (Phiếu Nhập), ISSUE → PX (Phiếu Xuất).
     */
    public function codePrefix(): string
    {
        return match ($this) {
            self::RECEIPT    => 'PN',
            self::ISSUE      => 'PX',
            self::ADJUSTMENT => 'DC',
            self::WASTE      => 'HH',
            self::RETURN     => 'TH',
        };
    }
}
