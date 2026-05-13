<?php

namespace App\DTOs;

use App\Enums\MenuSourceType;

/**
 * Data Transfer Object cho request lấy Menu qua QR Code.
 *
 * ── Tương đồng với PlaceOrderDTO ────────────────────────────────────────────
 * PlaceOrderDTO  → mang context để tạo đơn (restaurantId, items, sourceChannel…)
 * GetMenuDTO     → mang context để lấy menu  (restaurantId, type, public_token…)
 *
 * Lý do dùng DTO (SRP):
 * - Controller chỉ map Request → DTO, không chứa business logic.
 * - Strategy nhận DTO đã type-safe, không parse raw array.
 * - `readonly` ngăn mutation vô tình khi truyền qua các tầng.
 *
 * Hai luồng QR:
 *   1. QR tĩnh  (Basic): public_token = restaurant_id, type = qr_static.
 *   2. QR bàn   (Pro)  : public_token = table_id,      type = qr_table.
 */
final readonly class GetMenuDTO
{
    /**
     * @param string         $publicToken  Giá trị từ QR (restaurant_id hoặc table_id tùy type)
     * @param MenuSourceType $type         Loại QR — quyết định Strategy nào được chọn
     */
    public function __construct(
        public string         $publicToken,
        public MenuSourceType $type,
    ) {}

    /**
     * Factory method — map validated request data sang DTO.
     * Controller gọi một dòng duy nhất.
     *
     * @param array<string, mixed> $data Dữ liệu đã qua FormRequest::validated()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicToken: $data['public_token'],
            type:        MenuSourceType::from($data['type']),
        );
    }
}
