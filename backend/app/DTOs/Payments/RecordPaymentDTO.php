<?php

namespace App\DTOs\Payments;

use App\Enums\PaymentMethod;

/**
 * RecordPaymentDTO — Data Transfer Object cho thao tác ghi nhận thanh toán.
 *
 * Đóng gói toàn bộ input cần thiết, giúp các class trong layer Service
 * không phụ thuộc trực tiếp vào HTTP Request.
 */
readonly class RecordPaymentDTO
{
    public function __construct(
        public readonly string        $orderId,
        public readonly PaymentMethod $method,
        public readonly string        $createdBy,
        public readonly ?float        $amount      = null,
        public readonly ?string       $referenceNo = null,
    ) {}

    public static function fromArray(string $orderId, string $createdBy, array $data): self
    {
        return new self(
            orderId:     $orderId,
            method:      PaymentMethod::from($data['payment_method']),
            createdBy:   $createdBy,
            amount:      isset($data['amount']) ? (float) $data['amount'] : null,
            referenceNo: $data['reference_no'] ?? null,
        );
    }
}
