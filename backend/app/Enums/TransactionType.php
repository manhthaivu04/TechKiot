<?php

namespace App\Enums;

/**
 * TransactionType — Loại giao dịch kho (biến động tồn kho).
 *
 * Khác với DocumentType (loại chứng từ):
 * - DocumentType gắn với Stock Document (phiếu nhập/xuất/điều chỉnh...).
 * - TransactionType gắn với dòng ghi sổ kho (inventory_transactions).
 * - Thêm RECIPE_USE cho việc trừ kho khi order (theo BOM).
 */
enum TransactionType: string
{
    case RECEIPT    = 'receipt';      // Nhập kho (mua hàng, nhận từ NCC)
    case ISSUE      = 'issue';        // Xuất kho (xuất cho bếp, quầy bar)
    case ADJUSTMENT = 'adjustment';   // Điều chỉnh (kiểm kê, chênh lệch)
    case WASTE      = 'waste';        // Hao hụt (hư hỏng, hết hạn)
    case RETURN     = 'return';       // Trả hàng (trả lại NCC)
    case RECIPE_USE = 'recipe_use';   // Trừ kho theo công thức khi order

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT    => 'Nhập kho',
            self::ISSUE      => 'Xuất kho',
            self::ADJUSTMENT => 'Điều chỉnh',
            self::WASTE      => 'Hao hụt',
            self::RETURN     => 'Trả hàng',
            self::RECIPE_USE => 'Sử dụng theo công thức',
        };
    }

    /**
     * Hệ số: +1 (cộng kho), -1 (trừ kho), 0 (điều chỉnh — logic riêng).
     */
    public function sign(): int
    {
        return match ($this) {
            self::RECEIPT, self::RETURN     => 1,
            self::ISSUE, self::WASTE, self::RECIPE_USE => -1,
            self::ADJUSTMENT                => 0,
        };
    }
}
