<?php

namespace App\States\Order;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Log;

abstract class OrderState
{
    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Xác định xem từ state hiện tại có thể chuyển sang state mới không.
     */
    abstract public function canTransitionTo(OrderStatus $newStatus): bool;

    /**
     * Tên hiển thị của State (label)
     */
    abstract public function label(): string;

    /**
     * Lấy giá trị Enum tương ứng với State
     */
    abstract public function getValue(): OrderStatus;

    /**
     * Kiểm tra xem ở trạng thái này đơn hàng có cho phép chỉnh sửa không.
     */
    public function isEditable(): bool
    {
        return false;
    }

    /**
     * Kiểm tra xem ở trạng thái này có được phép thêm món mới không.
     */
    public function canAddItems(): bool
    {
        return true;
    }

    /**
     * Kiểm tra xem ở trạng thái này có được phép bỏ món / cập nhật số lượng không.
     */
    public function canUpdateItems(): bool
    {
        return true;
    }

    /**
     * Thực hiện chuyển trạng thái
     * Đặt logic thực thi side-effects (nếu có) khi chuyển đổi trạng thái tại đây.
     */
    public function transitionTo(OrderStatus $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Không thể chuyển đơn hàng từ [{$this->label()}] sang trạng thái mới."
            );
        }

        $this->order->update(['status' => $newStatus]);
        
        Log::info("Order [{$this->order->order_code}] transitioned: {$this->getValue()->value} → {$newStatus->value}");
        
        // Cập nhật lại context state của model (nếu gọi tiếp $order->state())
        $this->order->refresh();
    }
}
