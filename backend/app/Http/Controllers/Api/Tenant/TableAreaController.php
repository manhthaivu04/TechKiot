<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTableAreaRequest;
use App\Http\Requests\Tenant\UpdateTableAreaRequest;
use App\Http\Resources\TableAreaResource;
use App\Contracts\Services\TableAreaServiceInterface;
use App\DTOs\TableAreaDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TableAreaController — CRUD khu vực bàn.
 *
 * SRP: Controller chỉ nhận request, chuyển sang DTO, gọi service, trả response.
 * DIP: Inject TableAreaServiceInterface, không phụ thuộc implementation cụ thể.
 *
 * Luồng: Request → FormRequest (validate) → Controller → DTO → Service → Repository → Model
 */
class TableAreaController extends Controller
{
    public function __construct(
        protected TableAreaServiceInterface $tableAreaService
    ) {}

    /**
     * GET /api/tenant/table-areas
     * Lấy danh sách khu vực bàn, sắp xếp theo display_order → created_at.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $areas = $this->tableAreaService->getAllAreas($restaurantId);

        return response()->json([
            'data' => TableAreaResource::collection($areas),
        ]);
    }

    /**
     * POST /api/tenant/table-areas
     * Tạo khu vực bàn mới.
     */
    public function store(StoreTableAreaRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $dto = TableAreaDTO::fromArray($request->validated());
        $area = $this->tableAreaService->createArea($restaurantId, $dto);

        return response()->json([
            'data'    => new TableAreaResource($area),
            'message' => 'Tạo khu vực bàn thành công.',
        ], 201);
    }

    /**
     * GET /api/tenant/table-areas/{id}
     * Xem chi tiết khu vực bàn.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $area = $this->tableAreaService->getAreaById($id, $restaurantId);

        return response()->json([
            'data' => new TableAreaResource($area),
        ]);
    }

    /**
     * PUT/PATCH /api/tenant/table-areas/{id}
     * Cập nhật khu vực bàn.
     */
    public function update(UpdateTableAreaRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $area = $this->tableAreaService->updateArea($id, $restaurantId, $request->validated());

        return response()->json([
            'data'    => new TableAreaResource($area),
            'message' => 'Cập nhật khu vực bàn thành công.',
        ]);
    }

    /**
     * DELETE /api/tenant/table-areas/{id}
     * Xóa khu vực bàn. Các bàn thuộc khu vực sẽ set area_id = null.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $this->tableAreaService->deleteArea($id, $restaurantId);

        return response()->json([
            'message' => 'Xóa khu vực bàn thành công.',
        ], 200);
    }
}
