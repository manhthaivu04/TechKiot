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
 * Ba luồng lấy menu:
 *   1. QR tĩnh  (Basic): public_token = restaurant_id, type = qr_static.
 *   2. QR bàn   (Pro)  : public_token = table_id,      type = qr_table.
 *   3. Tenant POS      : restaurantId từ auth,         type = tenant_pos.
 */
final readonly class GetMenuDTO
{
    /**
     * @param MenuSourceType $type          Loại nguồn — quyết định Strategy
     * @param string|null    $publicToken   Token từ QR (qr_static | qr_table)
     * @param string|null    $restaurantId  UUID nhà hàng (tenant_pos)
     */
    public function __construct(
        public MenuSourceType $type,
        public ?string        $publicToken = null,
        public ?string        $restaurantId = null,
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
            type:         MenuSourceType::from($data['type']),
            publicToken:  $data['public_token'],
        );
    }

    public static function forTenant(string $restaurantId): self
    {
        return new self(
            type:         MenuSourceType::TenantPos,
            restaurantId: $restaurantId,
        );
    }
}
