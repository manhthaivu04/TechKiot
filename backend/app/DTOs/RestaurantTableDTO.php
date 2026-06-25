<?php

namespace App\DTOs;

use App\Enums\TableStatus;

/**
 * DTO cho RestaurantTable — dữ liệu validate từ Controller xuống Service.
 *
 * uid và qr_token có thể null khi tạo mới (service sẽ tự sinh).
 * Khi cập nhật, uid có thể được truyền lên để thay đổi mã bàn.
 */
final readonly class RestaurantTableDTO
{
    public function __construct(
        public string       $name,
        public ?string      $areaId = null,
        public ?string      $uid = null,
        public int          $capacity = 4,
        public TableStatus  $status = TableStatus::Available,
    ) {}

    /**
     * @param array<string, mixed> $data Dữ liệu đã qua FormRequest::validated()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'],
            areaId:   $data['area_id'] ?? null,
            uid:      $data['uid'] ?? null,
            capacity: $data['capacity'] ?? 4,
            status:   isset($data['status']) ? TableStatus::from($data['status']) : TableStatus::Available,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'area_id'  => $this->areaId,
            'uid'      => $this->uid,
            'name'     => $this->name,
            'capacity' => $this->capacity,
            'status'   => $this->status->value,
        ], fn ($v) => $v !== null);
    }
}
