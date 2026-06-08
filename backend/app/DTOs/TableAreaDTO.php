<?php

namespace App\DTOs;

/**
 * DTO cho TableArea — mang dữ liệu đã validate từ Controller xuống Service.
 *
 * Readonly để ngăn mutation vô tình khi truyền qua các tầng (SRP).
 * Service nhận DTO type-safe, không parse raw array.
 */
final readonly class TableAreaDTO
{
    public function __construct(
        public string  $name,
        public ?string $description = null,
        public int     $displayOrder = 0,
    ) {}

    /**
     * Factory method — map validated request data sang DTO.
     *
     * @param array<string, mixed> $data Dữ liệu đã qua FormRequest::validated()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:         $data['name'],
            description:  $data['description'] ?? null,
            displayOrder: $data['display_order'] ?? 0,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'description'   => $this->description,
            'display_order' => $this->displayOrder,
        ];
    }
}
