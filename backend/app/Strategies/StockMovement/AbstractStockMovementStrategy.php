<?php

namespace App\Strategies\StockMovement;

use App\Enums\TransactionType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\StockDocument;

/**
 * AbstractStockMovementStrategy — Base class cung cấp helper chung cho các Strategy.
 *
 * Template Method Pattern: các concrete strategy chỉ cần định nghĩa:
 *   - getTransactionType(): loại giao dịch để ghi sổ.
 *   - calculateNewQuantity(): logic tính tồn mới.
 */
abstract class AbstractStockMovementStrategy implements StockMovementStrategy
{
    /**
     * Loại giao dịch tương ứng (để ghi vào inventory_transactions).
     */
    abstract protected function getTransactionType(): TransactionType;

    /**
     * Tính tồn kho mới dựa trên tồn hiện tại và quantity trên chứng từ.
     *
     * @param float $currentQuantity Tồn kho hiện tại
     * @param float $documentQuantity Số lượng trên dòng chứng từ
     * @return float Tồn kho mới sau giao dịch
     */
    abstract protected function calculateNewQuantity(float $currentQuantity, float $documentQuantity): float;

    /**
     * {@inheritDoc}
     */
    public function process(StockDocument $document): void
    {
        foreach ($document->stockDocumentItems as $line) {
            // 1. Tìm hoặc tạo bản ghi Inventory (firstOrCreate đảm bảo tồn tại)
            $inventory = Inventory::firstOrCreate(
                [
                    'restaurant_id' => $document->restaurant_id,
                    'warehouse_id'  => $document->warehouse_id,
                    'item_id'       => $line->item_id,
                ],
                ['quantity' => 0]
            );

            // 2. Ghi nhận trạng thái trước biến động
            $beforeQuantity = (float) $inventory->quantity;

            // 3. Tính tồn mới (delegate cho concrete strategy)
            $afterQuantity = $this->calculateNewQuantity($beforeQuantity, (float) $line->quantity);

            // 4. Cập nhật Inventory
            $inventory->update(['quantity' => $afterQuantity]);

            // 5. Ghi sổ kho (Immutable Audit Log)
            InventoryTransaction::create([
                'restaurant_id'  => $document->restaurant_id,
                'warehouse_id'   => $document->warehouse_id,
                'item_id'        => $line->item_id,
                'transaction_type' => $this->getTransactionType()->value,
                'reference_type' => 'stock_document',
                'reference_id'   => $document->id,
                'quantity_change' => round($afterQuantity - $beforeQuantity, 3),
                'before_quantity' => $beforeQuantity,
                'after_quantity'  => $afterQuantity,
                'note'           => $document->note,
                'created_by'     => $document->created_by,
            ]);
        }
    }
}
