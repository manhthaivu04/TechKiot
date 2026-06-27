<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;

/**
 * ReceiptStrategy — Xử lý NHẬP KHO.
 *
 * Khi chứng từ nhập kho (receipt) được confirm:
 *   → CỘNG quantity từ chứng từ vào tồn kho hiện tại.
 *   → quantity_change = +quantity (dương).
 */
class ReceiptStrategy extends AbstractStockMovementStrategy
{
    protected function getTransactionType(): TransactionType
    {
        return TransactionType::RECEIPT;
    }

    protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float
    {
        return $currentQuantity + $documentQuantity;
    }
}
