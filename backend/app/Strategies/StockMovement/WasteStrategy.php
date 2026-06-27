<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;

/**
 * WasteStrategy — Xử lý HAO HỤT.
 *
 * Khi chứng từ hao hụt (waste) được confirm:
 *   → TRỪ quantity khỏi tồn kho (tương tự IssueStrategy).
 *   → quantity_change = -quantity (âm).
 *
 * Use case: Hàng hư hỏng, hết hạn, bể vỡ → giảm tồn kho.
 */
class WasteStrategy extends AbstractStockMovementStrategy
{
    protected function getTransactionType(): TransactionType
    {
        return TransactionType::WASTE;
    }

    protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float
    {
        return $currentQuantity - $documentQuantity;
    }
}
