<?php

namespace App\Contracts\Services;

use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Contract cho OrderService.
 * Đăng ký trong RepositoryServiceProvider để DI container biết bind.
 *
 * Lưu ý: recordPayment() đã được tách sang PaymentServiceInterface.
 */
interface OrderServiceInterface
{
    public function placeOrder(PlaceOrderDTO $dto): Order;

    /**
     * @param Order $order
     * @param \App\DTOs\PlaceOrderItemDTO[] $newItems
     */
    public function addItems(Order $order, array $newItems): Order;

    public function transition(Order $order, OrderStatus $newStatus): Order;

    public function removeItem(Order $order, string $itemId): Order;

    /**
     * @param Order $order
     * @param string $itemId
     * @param array{quantity?: int, note?: string} $data
     * @return Order
     */
    public function updateItem(Order $order, string $itemId, array $data): Order;
}
