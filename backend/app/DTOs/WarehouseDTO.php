<?php

namespace App\DTOs;

/**
 * DTO cho Warehouse — mang dữ liệu đã validate từ Controller xuống Service.
 *
 * Readonly để ngăn mutation vô tình khi truyền qua các tầng (SRP).
 * Service nhận DTO type-safe, không parse raw array.
 */
final readonly class WarehouseDTO
{
    public function __construct(
        public string $name,
        public bool   $isDefault = false,
    ) {}

    /**
     * Factory method — map validated request data sang DTO.
     *
     * @param array<string, mixed> $data Dữ liệu đã qua FormRequest::validated()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:      $data['name'],
            isDefault: $data['is_default'] ?? false,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'is_default' => $this->isDefault,
        ];
    }
}
