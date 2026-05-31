<?php

namespace App\Contracts\Orders;

use App\DTOs\PlaceOrderDTO;
use Closure;

/**
 * OrderPipeInterface — Contract cho các "ống lọc" trong Pipeline tạo đơn hàng.
 *
 * ── Chain of Responsibility (Pipeline) Pattern ───────────────────────────────
 * Mỗi Pipe thực thi một bước kiểm tra/xử lý. Nếu hợp lệ, gọi $next($dto)
 * để truyền cho Pipe tiếp theo. Nếu không hợp lệ, ném Exception chặn chuỗi.
 *
 * Chuỗi hiện tại:
 *   ValidateActiveItemsPipe   → [BASIC]   Check món có đang bán
 *   CheckInventoryStockPipe   → [PREMIUM] Check kho nguyên liệu (guard bằng feature)
 *   CalculatePricingPipe      → [BASIC]   Tính toán giá tiền
 *
 * Thêm bước mới (VD: CheckLoyaltyPipe) → chỉ tạo file + đăng ký vào Pipeline.
 * Không sửa bất kỳ Pipe nào hiện có — Open/Closed Principle.
 */
interface OrderPipeInterface
{
    /**
     * @param  PlaceOrderDTO $dto   Dữ liệu đặt hàng (bất biến)
     * @param  Closure       $next  Callback để gọi Pipe tiếp theo
     * @return mixed                Kết quả từ Pipe cuối cùng
     */
    public function handle(PlaceOrderDTO $dto, Closure $next): mixed;
}
