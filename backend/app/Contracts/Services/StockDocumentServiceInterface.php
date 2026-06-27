<?php

namespace App\Contracts\Services;

use App\DTOs\StockDocumentDTO;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract cho StockDocument Service.
 *
 * Controller chỉ inject interface này (DIP), không biết implementation cụ thể.
 */
interface StockDocumentServiceInterface
{
    /** Lấy danh sách chứng từ của nhà hàng */
    public function list(string $restaurantId): Collection;

    /** Tạo chứng từ draft + các dòng items */
    public function store(string $restaurantId, StockDocumentDTO $dto, ?string $userId = null);

    /** Xác nhận chứng từ (draft → confirmed) via State Pattern */
    public function confirm(string $documentId): void;

    /** Huỷ chứng từ (draft → cancelled) via State Pattern */
    public function cancel(string $documentId): void;
}
