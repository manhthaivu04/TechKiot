<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;

/**
 * ReturnStrategy — Xử lý TRẢ HÀNG.
 *
 * Khi chứng từ trả hàng (return) được confirm:
 *   → CỘNG quantity vào tồn kho (tương tự ReceiptStrategy).
 *   → quantity_change = +quantity (dương).
 *
 * Use case: Hàng trả lại NCC bị từ chối → nhập lại kho.
 * Hoặc khách trả hàng → nhập lại tồn.
 */
class ReturnStrategy extends AbstractStockMovementStrategy
{
    protected function getTransactionType(): TransactionType
    {
        return TransactionType::RETURN;
    }

    protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float
    {
        return $currentQuantity + $documentQuantity;
    }
}
