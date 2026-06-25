<?php

namespace App\DTOs;

use App\Enums\OrderSourceChannel;

/**
 * Data Transfer Object cho việc đặt đơn hàng mới.
 *
 * Lý do dùng DTO thay vì truyền raw array:
 * 1. Type-safety: IDE và static analyzer hiểu rõ cấu trúc dữ liệu.
 * 2. Immutability: readonly properties ngăn mutation vô tình trong Service.
 * 3. Single Responsibility: tách biệt validation (Request) khỏi business logic (Service).
 *
 * Thiết kế để hỗ trợ sẵn Pro fields (tableId, guestCount) với default null/1,
 * giúp OrderService không cần thay đổi khi nâng cấp gói — Open/Closed Principle.
 */
final readonly class PlaceOrderDTO
{
    /**
     * @param string                 $restaurantId  ID nhà hàng (từ authenticated user)
     * @param string                 $createdBy     ID user tạo đơn
     * @param OrderSourceChannel     $sourceChannel Kênh tạo đơn (cashier, qr_static...)
     * @param list<PlaceOrderItemDTO> $items        Danh sách món đặt
     * @param string|null            $tableId       Pro: TABLE_MANAGEMENT — ID bàn (nullable)
     * @param string|null            $note          Ghi chú cho toàn đơn
     * @param string|null            $customerName  Tên khách (QR order)
     * @param string|null            $customerPhone SĐT khách (QR order)
     * @param string|null            $customerReference Số bàn / mã QR
     * @param int                    $guestCount    Số khách (mặc định 1 cho takeaway)
     * @param float                  $discountAmount Giảm giá thủ công (future: voucher)
     * @param float                  $taxRate       Thuế suất (0.0 = 0%, 0.1 = 10%)
     */
    public function __construct(
        public string             $restaurantId,
        public string             $createdBy,
        public OrderSourceChannel $sourceChannel,
        /** @var list<PlaceOrderItemDTO> */
        public array              $items,

        // ── Pro fields (Basic để null, Pro truyền giá trị) ──────────────────
        public ?string            $serviceType      = null,
        public ?string            $tableId          = null, // Pro: TABLE_MANAGEMENT
        public ?string            $note             = null,
        public ?string            $customerName     = null,
        public ?string            $customerPhone    = null,
        public ?string            $customerReference = null,
        public int                $guestCount       = 1,
        public float              $discountAmount   = 0.0,
        public float              $taxRate          = 0.0,  // 0.0 = miễn thuế (Basic)
    ) {}

    /**
     * Factory method tạo DTO từ validated request data.
     * Tập trung mapping logic tại một nơi, Controller gọi 1 dòng.
     *
     * @param array<string, mixed> $data Dữ liệu đã validate từ FormRequest
     */
    public static function fromArray(string $restaurantId, string $createdBy, array $data): self
    {
        return new self(
            restaurantId:       $restaurantId,
            createdBy:          $createdBy,
            sourceChannel:      OrderSourceChannel::from($data['source_channel']),
            items:              array_map(
                                    fn(array $item) => PlaceOrderItemDTO::fromArray($item),
                                    $data['items']
                                ),
            serviceType:        isset($data['service_type']) ? strtolower($data['service_type']) : null,
            tableId:            $data['table_id']           ?? null,
            note:               $data['note']               ?? null,
            customerName:       $data['customer_name']      ?? null,
            customerPhone:      $data['customer_phone']     ?? null,
            customerReference:  $data['customer_reference'] ?? null,
            guestCount:         (int) ($data['guest_count']      ?? 1),
            discountAmount:     (float) ($data['discount_amount'] ?? 0.0),
            taxRate:            (float) ($data['tax_rate']        ?? 0.0),
        );
    }
}
