<?php

namespace App\DTOs;

/**
 * DTO cho một dòng nguyên liệu trong công thức (BOM).
 *
 * Frontend gửi lên mảng ingredients, mỗi phần tử gồm:
 *   - ingredient_id: UUID của nguyên liệu (item có item_type = INGREDIENT)
 *   - quantity:       Số lượng cần dùng (VD: 0.200 kg thịt bò)
 *
 * DTO này đảm bảo:
 *   1. Type-safe — IDE autocomplete, không đoán key array.
 *   2. Validate cơ bản trước khi truyền vào Repository.
 *   3. Dễ mở rộng thêm field (VD: `unit_override`, `note`).
 */
final readonly class RecipeIngredientDTO
{
    public function __construct(
        public string $ingredientId,
        public float  $quantity,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ingredientId: $data['ingredient_id'],
            quantity:      (float) $data['quantity'],
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
