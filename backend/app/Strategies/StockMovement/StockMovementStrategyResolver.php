<?php

namespace App\Strategies\StockMovement;

use App\Enums\DocumentType;

/**
 * StockMovementStrategyResolver — Resolve Strategy từ DocumentType.
 *
 * Factory/Resolver Pattern: map document_type → concrete strategy.
 * Tách biệt logic resolve khỏi Listener, dễ mở rộng khi thêm loại chứng từ mới.
 *
 * @example
 *   $resolver = new StockMovementStrategyResolver();
 *   $strategy = $resolver->resolve(DocumentType::RECEIPT);
 *   $strategy->process($document);
 */
class StockMovementStrategyResolver
{
    /**
     * Resolve concrete strategy từ DocumentType enum.
     *
     * @throws \InvalidArgumentException Khi DocumentType chưa được map
     */
    public function resolve(DocumentType $documentType): StockMovementStrategy
    {
        return match ($documentType) {
            DocumentType::RECEIPT    => new ReceiptStrategy(),
            DocumentType::ISSUE      => new IssueStrategy(),
            DocumentType::ADJUSTMENT => new AdjustmentStrategy(),
            DocumentType::WASTE      => new WasteStrategy(),
            DocumentType::RETURN     => new ReturnStrategy(),
        };
    }
}
