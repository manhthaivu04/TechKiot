<?php

namespace App\Services\Orders\Actions;

use App\DTOs\PlaceOrderDTO;
use App\Events\OrderPlaced;
use App\Models\Order;
use App\Services\Orders\Pipes\CalculatePricingPipe;
use App\Services\Orders\Pipes\CheckInventoryStockPipe;
use App\Services\Orders\Pipes\ValidateActiveItemsPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

/**
 * PlaceOrderAction — Orchestrator tạo đơn hàng mới.
 *
 * ── Sau khi tái cấu trúc (Pipeline + Observer) ───────────────────────────────
 * Class này giờ chỉ làm đúng 1 việc: điều phối Pipeline và fire Event.
 * Mọi logic chi tiết đã được phân tán vào đúng chỗ:
 *
 *   TRƯỚC (cũ):
 *     PlaceOrderAction → [validate] + [tính tiền] + [lưu DB] + [gọi Strategy]
 *     → Vi phạm SRP: 1 class, 4 trách nhiệm.
 *
 *   SAU (mới):
 *     PlaceOrderAction → Pipeline → fire(OrderPlaced)
 *       Pipeline:  ValidateActiveItemsPipe → CheckInventoryStockPipe → CalculatePricingPipe
 *       Observer:  NotifyKitchenListener + HandleOrderSourceStrategyListener + DeductInventoryListener
 *     → SRP: mỗi bước trong chuỗi có 1 file riêng, 1 trách nhiệm riêng.
 *
 * ── OCP ──────────────────────────────────────────────────────────────────────
 * Thêm tính năng mới (VD: CheckLoyaltyPipe, SendSmsListener)?
 * → Tạo class mới + đăng ký. Không sửa file này.
 */
class PlaceOrderAction
{
    public function __construct(
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Chuỗi Pipeline xử lý trước khi lưu DB.
     * Thứ tự quan trọng: Validate trước → Check kho → Tính tiền & Lưu.
     *
     * Thêm gói mới → chỉ thêm class vào mảng này.
     */
    private array $pipes = [
        ValidateActiveItemsPipe::class,     // [BASIC]   Kiểm tra món hợp lệ
        CheckInventoryStockPipe::class,     // [PREMIUM] Kiểm tra kho (guard bên trong)
        CalculatePricingPipe::class,        // [BASIC]   Tính tiền & Lưu DB
    ];

    /**
     * @throws \DomainException Nếu Pipeline bị chặn bởi bất kỳ Pipe nào
     */
    public function execute(PlaceOrderDTO $dto): Order
    {
        $order = DB::transaction(function () use ($dto): Order {

            // Chạy toàn bộ Pipeline
            $this->pipeline
                ->send($dto)
                ->through($this->pipes)
                ->thenReturn();

            // Lấy Order đã được tạo bởi CalculatePricingPipe
            /** @var Order $order */
            $order = request()->attributes->get('_created_order');

            return $order;
        });

        // Fire Event SAU KHI transaction đã commit thành công.
        // Điều này đảm bảo Frontend gọi API (invalidateQueries) sẽ thấy được dữ liệu thật trong DB.
        OrderPlaced::dispatch($order, $dto);

        return $order;
    }
}
