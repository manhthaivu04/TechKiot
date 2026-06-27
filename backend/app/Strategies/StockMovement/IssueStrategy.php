<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;

/**
 * IssueStrategy — Xử lý XUẤT KHO.
 *
 * Khi chứng từ xuất kho (issue) được confirm:
 *   → TRỪ quantity từ chứng từ khỏi tồn kho hiện tại.
 *   → quantity_change = -quantity (âm).
 */
class IssueStrategy extends AbstractStockMovementStrategy
{
    protected function getTransactionType(): TransactionType
    {
        return TransactionType::ISSUE;
    }

    protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float
    {
        return $currentQuantity - $documentQuantity;
    }
}
