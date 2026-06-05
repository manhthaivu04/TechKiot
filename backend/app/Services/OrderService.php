<?php

namespace App\Services;

use App\Contracts\Services\OrderServiceInterface;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\Actions\AddItemsToOrderAction;
use App\Services\Orders\Actions\PlaceOrderAction;
use App\Services\Orders\Actions\RecordPaymentAction;
use App\Services\Orders\Actions\TransitionOrderAction;

/**
 * OrderService — Facade mong manh (Thin Facade) cho module Order.
 *
 * ── Sau khi tái cấu trúc sang Action Classes ─────────────────────────────────
 * Business logic đã được chuyển hoàn toàn vào 3 Action Class chuyên biệt:
 *
 *   PlaceOrderAction     → Tạo đơn hàng mới (kèm Strategy cho side-effects)
 *   RecordPaymentAction  → Ghi nhận thanh toán (hỗ trợ split payment)
 *   TransitionOrderAction → Chuyển trạng thái đơn
 *
 * OrderService chỉ còn giữ vai trò ĐẦU MỐI DUY NHẤT (Single Entry Point)
 * mà các Controller đang inject — đảm bảo backward compatibility tuyệt đối.
 * Không cần sửa bất kỳ dòng nào trong Controller.
 *
 * ── Lợi ích của kiến trúc này ────────────────────────────────────────────────
 * SRP  : Mỗi Action chỉ làm đúng 1 việc, dễ đọc và dễ test độc lập.
 * OCP  : Thêm AddItemsAction, CancelItemAction → tạo file mới, không sửa file cũ.
 * ISP  : Controller muốn dùng trực tiếp Action Class cũng được — inject thẳng.
 * DIP  : OrderService inject các Action thay vì gọi logic thô.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly PlaceOrderAction        $placeOrderAction,
        private readonly AddItemsToOrderAction   $addItemsToOrderAction,
        private readonly RecordPaymentAction     $recordPaymentAction,
        private readonly TransitionOrderAction   $transitionOrderAction,
        private readonly \App\Services\Orders\Actions\RemoveOrderItemAction $removeOrderItemAction,
        private readonly \App\Services\Orders\Actions\UpdateOrderItemAction $updateOrderItemAction,
    ) {}

    public function placeOrder(PlaceOrderDTO $dto): Order
    {
        return $this->placeOrderAction->execute($dto);
    }

    public function addItems(Order $order, array $newItems): Order
    {
        return $this->addItemsToOrderAction->execute($order, $newItems);
    }

    public function recordPayment(
        Order   $order,
        ?float  $amount,
        string  $method,
        string  $createdBy,
        ?string $referenceNo = null,
    ): Payment {
        return $this->recordPaymentAction->execute($order, $amount, $method, $createdBy, $referenceNo);
    }

    public function transition(Order $order, OrderStatus $newStatus): Order
    {
        return $this->transitionOrderAction->execute($order, $newStatus);
    }

    public function removeItem(Order $order, string $itemId): Order
    {
        return $this->removeOrderItemAction->execute($order, $itemId);
    }

    public function updateItem(Order $order, string $itemId, array $data): Order
    {
        return $this->updateOrderItemAction->execute($order, $itemId, $data);
    }
}
