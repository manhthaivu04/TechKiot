<?php

namespace App\DTOs;

/**
 * DTO cho từng dòng món trong PlaceOrderDTO.
 *
 * Tách ra class riêng thay vì dùng array lồng nhau để:
 * 1. IDE autocomplete khi loop qua $dto->items.
 * 2. Validation type-safe (itemId phải là string, quantity > 0).
 * 3. Dễ mở rộng thêm field sau (e.g., `modifiers` cho Premium: RECIPE_MANAGEMENT).
 */
final readonly class PlaceOrderItemDTO
{
    public function __construct(
        public string  $itemId,
        public int     $quantity,
        public ?string $note = null,
        // Premium: RECIPE_MANAGEMENT — biến thể, topping, v.v.
        // public array $modifiers = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId:   $data['item_id'],
            quantity: (int) $data['quantity'],
            note:     $data['note'] ?? null,
        );
    }
}
