<?php

namespace App\Services\Orders\Actions;

use App\DTOs\AddItemsDTO;
use App\Enums\OrderSourceChannel;
use App\Enums\OrderStatus;
use App\Events\OrderItemsAdded;
use App\Models\Order;
use App\Services\Orders\Actions\TransitionOrderAction;
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
        private readonly TransitionOrderAction $transitionOrderAction,
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
        $order = DB::transaction(function () use ($order, $newItems): Order {
            $dto = new AddItemsDTO($order, $newItems);

            // Chạy qua Pipeline
            $this->pipeline
                ->send($dto)
                ->through($this->pipes)
                ->thenReturn();

            $order = $order->refresh();

            // Đơn cashier còn open → tự gửi xuống bếp (đồng bộ với CashierOrderStrategy)
            if ($order->status === OrderStatus::Open
                && $order->source_channel === OrderSourceChannel::Cashier
            ) {
                $this->transitionOrderAction->execute($order, OrderStatus::Cooking);
                $order = $order->refresh();
            }

            // Nếu đơn hàng đã phục vụ (Served), thì khi gọi thêm món phải kéo nó về lại Đang nấu (Cooking)
            // để nhân viên bếp/thu ngân thấy đơn hàng đỏ lên và tiến hành nấu & phục vụ.
            if ($order->status === \App\Enums\OrderStatus::Served) {
                $order->update(['status' => \App\Enums\OrderStatus::Cooking]);
                
                // Fire sự kiện để Cập nhật UI ngay lập tức
                \App\Events\OrderStatusTransitioned::dispatch(
                    $order,
                    \App\Enums\OrderStatus::Served,
                    \App\Enums\OrderStatus::Cooking
                );
            }

            return $order->load('items.item');
        });

        // Fire Event SAU KHI transaction đã commit
        OrderItemsAdded::dispatch($order, $newItems);

        return $order;
    }
}

