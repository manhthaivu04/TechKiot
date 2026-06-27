<?php

namespace App\Strategies\StockMovement;

use App\Models\StockDocument;

/**
 * StockMovementStrategy — Interface cho Strategy Pattern xử lý biến động kho.
 *
 * Mỗi loại chứng từ (DocumentType) có logic cập nhật kho khác nhau:
 * - Receipt/Return: CỘNG tồn kho.
 * - Issue/Waste: TRỪ tồn kho.
 * - Adjustment: GHI ĐÈ tồn kho (set quantity mới).
 *
 * Strategy được resolve bởi StockMovementStrategyResolver dựa trên document_type.
 */
interface StockMovementStrategy
{
    /**
     * Xử lý biến động kho cho toàn bộ items trong chứng từ.
     *
     * Phải được gọi bên trong DB::transaction() (đảm bảo bởi Listener).
     * Mỗi dòng item:
     *   1. Tìm/tạo bản ghi Inventory (warehouse + item).
     *   2. Cập nhật quantity trong Inventory.
     *   3. Ghi 1 dòng InventoryTransaction (audit log).
     */
    public function process(StockDocument $document): void;
}
