<?php

namespace App\Contracts\Payments;

use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;
use App\Models\Payment;

/**
 * PaymentGatewayInterface — Strategy Contract cho hệ thống thanh toán.
 *
 * ── Strategy Pattern ──────────────────────────────────────────────────────────
 * Mỗi phương thức thanh toán (Cash, Card, Transfer, Ewallet, v.v.) implement
 * interface này. PaymentGatewayFactory sẽ khởi tạo đúng Strategy dựa vào
 * PaymentMethod enum, thay vì dùng if/else trong service.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Thêm cổng thanh toán mới (VNPay, MoMo)? → Tạo class mới implement interface này.
 * Không cần sửa bất kỳ file nào đã có.
 */
interface PaymentGatewayInterface
{
    /**
     * Xác thực và xử lý nghiệp vụ đặc thù trước khi ghi nhận Payment.
     *
     * Mỗi gateway có thể thực hiện logic riêng:
     * - Cash: không cần validate gì thêm.
     * - Card/Transfer: yêu cầu reference_no bắt buộc.
     * - Ewallet (MoMo, ZaloPay): có thể gọi API xác thực giao dịch.
     *
     * @throws \DomainException Nếu nghiệp vụ không hợp lệ (VD: thiếu reference_no)
     */
    public function validate(RecordPaymentDTO $dto): void;

    /**
     * Tạo Payment record. Logic lưu DB chung nằm trong ProcessPaymentAction.
     * Method này chỉ xử lý logic ĐẶC THÙ của gateway (nếu có).
     *
     * Ví dụ:
     * - Ewallet: gọi API để tạo payment intent / lấy URL redirect.
     * - Cash: không cần làm gì thêm, return null.
     *
     * @return array<string, mixed>|null Extra data để merge vào Payment record (nếu có)
     */
    public function prepare(Order $order, RecordPaymentDTO $dto): ?array;

    /**
     * Trả về nhãn hiển thị của gateway (dùng cho log/audit).
     */
    public function label(): string;
}
