<?php

namespace App\Services\Orders\Actions;

use App\DTOs\AddItemsDTO;
use App\Events\OrderItemsAdded;
use App\Models\Order;
use App\Services\Orders\Pipes\AddItems\AddItemsCheckInventoryPipe;
use App\Services\Orders\Pipes\AddItems\AddItemsSavePipe;
use App\Services\Orders\Pipes\AddItems\AddItemsValidatePipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

/**
 * AddItemsToOrderAction — Xử lý nghiệp vụ khách gọi thêm món.
 *
 * ── Tái cấu trúc (Pipeline + Observer) ──────────────────────────────────────
 * Giống như PlaceOrderAction, class này giờ chỉ điều phối.
 * - Pipeline: Kiểm tra tính hợp lệ -> Lưu xuống DB
 * - Event: Bắn sự kiện OrderItemsAdded để các Listeners (Bếp, KDS) tự xử lý.
 */
class AddItemsToOrderAction
{
    public function __construct(
        private readonly Pipeline $pipeline,
    ) {}

    private array $pipes = [
        AddItemsValidatePipe::class,       // [BASIC] Validate trạng thái đơn và món ăn
        AddItemsCheckInventoryPipe::class, // [PREMIUM] Check kho nguyên liệu
        AddItemsSavePipe::class,           // [BASIC] Lưu món và cộng dồn tiền
    ];

    /**
     * @param Order $order Đơn hàng hiện tại
     * @param list<\App\DTOs\PlaceOrderItemDTO> $newItems Danh sách món gọi thêm
     * @return Order
     */
    public function execute(Order $order, array $newItems): Order
    {
        return DB::transaction(function () use ($order, $newItems): Order {
            $dto = new AddItemsDTO($order, $newItems);

            // Chạy qua Pipeline
            $this->pipeline
                ->send($dto)
                ->through($this->pipes)
                ->thenReturn();

            // Fire Event để báo Bếp / trigger Strategy
            OrderItemsAdded::dispatch($order, $newItems);

            return $order->refresh()->load('items.item');
        });
    }
}

