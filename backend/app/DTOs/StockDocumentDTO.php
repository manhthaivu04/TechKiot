<?php

namespace App\DTOs;

use App\Enums\DocumentType;

/**
 * DTO cho chứng từ kho — mang dữ liệu đã validate từ Controller xuống Service.
 *
 * Chứa thông tin header (warehouse, type, note) và danh sách items.
 * Readonly để ngăn mutation vô tình khi truyền qua các tầng (SRP).
 */
final readonly class StockDocumentDTO
{
    /**
     * @param StockDocumentItemDTO[] $items
     */
    public function __construct(
        public string       $warehouseId,
        public DocumentType $documentType,
        public ?string      $note = null,
        public array        $items = [],
    ) {}

    /**
     * Factory method — map validated request data sang DTO.
     *
     * @param array<string, mixed> $data Dữ liệu đã qua FormRequest::validated()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            warehouseId:  $data['warehouse_id'],
            documentType: DocumentType::from($data['document_type']),
            note:         $data['note'] ?? null,
            items:        StockDocumentItemDTO::collection($data['items'] ?? []),
        );
    }
}
