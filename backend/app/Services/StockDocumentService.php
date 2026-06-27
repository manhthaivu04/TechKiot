<?php

namespace App\Services;

use App\Contracts\Repositories\StockDocumentRepositoryInterface;
use App\Contracts\Services\StockDocumentServiceInterface;
use App\DTOs\StockDocumentDTO;
use App\DTOs\StockDocumentItemDTO;
use App\Enums\DocumentStatus;
use App\Events\StockDocumentConfirmed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * StockDocumentService — xử lý business logic cho chứng từ kho.
 *
 * SRP: Service chỉ chứa logic nghiệp vụ.
 * DIP: Inject repository qua interface, không phụ thuộc implementation.
 *
 * Flow tạo chứng từ:
 *   1. Sinh mã tự động (VD: PN001) dựa trên document_type.
 *   2. Tạo header (stock_documents) với status = draft.
 *   3. Tạo các dòng chi tiết (stock_document_items) kèm total_cost tính tự động.
 *   4. Confirm/Cancel delegate cho State Pattern.
 */
class StockDocumentService implements StockDocumentServiceInterface
{
    public function __construct(
        protected StockDocumentRepositoryInterface $stockDocumentRepository
    ) {}

    public function list(string $restaurantId): Collection
    {
        return $this->stockDocumentRepository->getByRestaurantId($restaurantId);
    }

    /**
     * Tạo chứng từ mới với status = draft.
     *
     * Sử dụng DB::transaction để đảm bảo atomicity:
     * nếu tạo dòng items thất bại, header cũng rollback.
     */
    public function store(string $restaurantId, StockDocumentDTO $dto, ?string $userId = null)
    {
        return DB::transaction(function () use ($restaurantId, $dto, $userId) {
            // 1. Sinh mã chứng từ tự động
            $code = $this->stockDocumentRepository->generateCode(
                $restaurantId,
                $dto->documentType->codePrefix()
            );

            // 2. Tạo header
            $document = $this->stockDocumentRepository->create([
                'restaurant_id' => $restaurantId,
                'warehouse_id'  => $dto->warehouseId,
                'document_type' => $dto->documentType->value,
                'code'          => $code,
                'status'        => DocumentStatus::DRAFT->value,
                'note'          => $dto->note,
                'created_by'    => $userId,
            ]);

            // 3. Tạo các dòng chi tiết
            foreach ($dto->items as $itemDto) {
                $document->stockDocumentItems()->create([
                    'item_id'    => $itemDto->itemId,
                    'quantity'   => $itemDto->quantity,
                    'unit_cost'  => $itemDto->unitCost,
                    'total_cost' => round($itemDto->quantity * $itemDto->unitCost, 2),
                ]);
            }

            // Load relations để trả về response đầy đủ
            return $document->load(['warehouse', 'createdBy', 'stockDocumentItems.item']);
        });
    }

    /**
     * Xác nhận chứng từ.
     * Delegate cho State Pattern — chỉ DraftState cho phép confirm().
     * Sau khi chuyển status → dispatch Event để ghi sổ kho (Observer Pattern).
     *
     * @throws \DomainException khi trạng thái không cho phép
     */
    public function confirm(string $documentId): void
    {
        $document = $this->stockDocumentRepository->findByIdOrFail($documentId);

        // 1. State Pattern: chuyển draft → confirmed
        $document->state()->confirm();

        // 2. Observer Pattern: dispatch event → Listener ghi sổ kho
        StockDocumentConfirmed::dispatch($document);
    }

    /**
     * Huỷ chứng từ.
     * Delegate cho State Pattern — chỉ DraftState cho phép cancel().
     *
     * @throws \DomainException khi trạng thái không cho phép
     */
    public function cancel(string $documentId): void
    {
        $document = $this->stockDocumentRepository->findByIdOrFail($documentId);
        $document->state()->cancel();
    }
}
