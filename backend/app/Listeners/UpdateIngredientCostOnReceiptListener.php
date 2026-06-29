<?php

namespace App\Listeners;

use App\Enums\DocumentType;
use App\Enums\ItemType;
use App\Events\StockDocumentConfirmed;
use App\Services\ItemCostPriceService;

/**
 * Cập nhật giá vốn nguyên liệu (bình quân gia quyền) khi xác nhận phiếu nhập.
 *
 * Phải chạy TRƯỚC ProcessStockMovementListener để đọc tồn kho trước khi cộng.
 */
class UpdateIngredientCostOnReceiptListener
{
    public function __construct(
        private readonly ItemCostPriceService $itemCostPriceService,
    ) {}

    public function handle(StockDocumentConfirmed $event): void
    {
        $document = $event->stockDocument;

        if ($document->document_type !== DocumentType::RECEIPT) {
            return;
        }

        $document->load('stockDocumentItems.item');

        foreach ($document->stockDocumentItems as $line) {
            $item = $line->item;

            if (! $item || $item->item_type !== ItemType::INGREDIENT) {
                continue;
            }

            $currentQty = $this->itemCostPriceService->getTotalInventoryQuantity(
                $document->restaurant_id,
                $item->id,
            );

            $newCost = $this->itemCostPriceService->calculateWeightedAverage(
                currentQuantity:  $currentQty,
                currentCost:      (float) $item->cost_price,
                receiptQuantity:  (float) $line->quantity,
                unitCost:         (float) $line->unit_cost,
            );

            $this->itemCostPriceService->updateIngredientCost($item, $newCost);
        }
    }
}
