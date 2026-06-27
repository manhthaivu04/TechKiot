<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreStockDocumentRequest;
use App\Http\Resources\StockDocumentResource;
use App\Contracts\Services\StockDocumentServiceInterface;
use App\DTOs\StockDocumentDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StockDocumentController — CRUD chứng từ kho.
 *
 * SRP: Controller chỉ nhận request, chuyển sang DTO, gọi service, trả response.
 * DIP: Inject StockDocumentServiceInterface, không phụ thuộc implementation cụ thể.
 *
 * Luồng: Request → FormRequest (validate) → Controller → DTO → Service → Repository → Model
 */
class StockDocumentController extends Controller
{
    public function __construct(
        protected StockDocumentServiceInterface $stockDocumentService
    ) {}

    /**
     * GET /api/tenant/stock-documents
     * Lấy danh sách chứng từ kho của nhà hàng.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $documents = $this->stockDocumentService->list($restaurantId);

        return response()->json([
            'data' => StockDocumentResource::collection($documents),
        ]);
    }

    /**
     * POST /api/tenant/stock-documents
     * Tạo chứng từ kho mới (status = draft).
     */
    public function store(StoreStockDocumentRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $userId = $request->user()->id;
        $dto = StockDocumentDTO::fromArray($request->validated());
        $document = $this->stockDocumentService->store($restaurantId, $dto, $userId);

        return response()->json([
            'data'    => new StockDocumentResource($document),
            'message' => 'Tạo chứng từ kho thành công.',
        ], 201);
    }

    /**
     * PATCH /api/tenant/stock-documents/{id}/confirm
     * Xác nhận chứng từ (draft → confirmed).
     */
    public function confirm(string $id): JsonResponse
    {
        $this->stockDocumentService->confirm($id);

        return response()->json([
            'message' => 'Xác nhận chứng từ thành công.',
        ]);
    }

    /**
     * PATCH /api/tenant/stock-documents/{id}/cancel
     * Huỷ chứng từ (draft → cancelled).
     */
    public function cancel(string $id): JsonResponse
    {
        $this->stockDocumentService->cancel($id);

        return response()->json([
            'message' => 'Huỷ chứng từ thành công.',
        ]);
    }
}
