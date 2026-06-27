<?php

namespace App\DTOs;

/**
 * DTO cho một dòng item trong chứng từ kho.
 *
 * Frontend gửi lên mảng items, mỗi phần tử gồm:
 *   - item_id:   UUID của nguyên liệu
 *   - quantity:  Số lượng nhập/xuất/điều chỉnh
 *   - unit_cost: Đơn giá (VD: giá mua từ NCC)
 */
final readonly class StockDocumentItemDTO
{
    public function __construct(
        public string $itemId,
        public float  $quantity,
        public float  $unitCost = 0,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId:   $data['item_id'],
            quantity: (float) $data['quantity'],
            unitCost: (float) ($data['unit_cost'] ?? 0),
        );
    }

    /**
     * Chuyển từ mảng request → danh sách DTO.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return self[]
     */
    public static function collection(array $items): array
    {
        return array_map(fn(array $item) => self::fromArray($item), $items);
    }
}
