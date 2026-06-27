<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;

/**
 * AdjustmentStrategy — Xử lý ĐIỀU CHỈNH KHO.
 *
 * Khi chứng từ điều chỉnh (adjustment) được confirm:
 *   → GHI ĐÈ tồn kho: set quantity = quantity trên chứng từ.
 *   → quantity_change = new_quantity - old_quantity (có thể dương hoặc âm).
 *
 * Use case: Sau khi kiểm kê thực tế, phát hiện chênh lệch → điều chỉnh cho khớp.
 */
class AdjustmentStrategy extends AbstractStockMovementStrategy
{
    protected function getTransactionType(): TransactionType
    {
        return TransactionType::ADJUSTMENT;
    }

    /**
     * Điều chỉnh: quantity trên chứng từ LÀ tồn mới (ghi đè).
     * Khác với Receipt/Issue: không cộng/trừ mà SET trực tiếp.
     */
    protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float
    {
        return $documentQuantity;
    }
}
