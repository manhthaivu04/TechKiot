<?php

namespace App\States\StockDocument;

use App\Enums\DocumentStatus;
use App\Models\StockDocument;

/**
 * DocumentState — Abstract base class cho State Pattern của chứng từ kho.
 *
 * Tương tự OrderState nhưng đơn giản hơn:
 * - Chỉ có 2 hành động: confirm() và cancel().
 * - Lifecycle: draft → confirmed / cancelled (2 terminal states).
 *
 * Mặc định throw exception — các concrete state override hành vi hợp lệ.
 * Đây là Template Method pattern kết hợp State pattern.
 */
abstract class DocumentState
{
    public function __construct(protected StockDocument $document) {}

    /**
     * Xác nhận chứng từ.
     * Mặc định throw exception — chỉ DraftState override.
     *
     * @throws \DomainException
     */
    public function confirm(): void
    {
        throw new \DomainException(
            "Không thể xác nhận chứng từ [{$this->document->code}] ở trạng thái [{$this->label()}]."
        );
    }

    /**
     * Huỷ chứng từ.
     * Mặc định throw exception — chỉ DraftState override.
     *
     * @throws \DomainException
     */
    public function cancel(): void
    {
        throw new \DomainException(
            "Không thể huỷ chứng từ [{$this->document->code}] ở trạng thái [{$this->label()}]."
        );
    }

    /**
     * Tên hiển thị của State.
     */
    abstract public function label(): string;

    /**
     * Giá trị Enum tương ứng.
     */
    abstract public function getValue(): DocumentStatus;
}
