<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreWarehouseRequest;
use App\Http\Requests\Tenant\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Contracts\Services\WarehouseServiceInterface;
use App\DTOs\WarehouseDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WarehouseController — CRUD kho chứa.
 *
 * SRP: Controller chỉ nhận request, chuyển sang DTO, gọi service, trả response.
 * DIP: Inject WarehouseServiceInterface, không phụ thuộc implementation cụ thể.
 *
 * Luồng: Request → FormRequest (validate) → Controller → DTO → Service → Repository → Model
 */
class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseServiceInterface $warehouseService
    ) {}

    /**
     * GET /api/tenant/warehouses
     * Lấy danh sách kho chứa của nhà hàng.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $warehouses = $this->warehouseService->list($restaurantId);

        return response()->json([
            'data' => WarehouseResource::collection($warehouses),
        ]);
    }

    /**
     * POST /api/tenant/warehouses
     * Tạo kho chứa mới.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $dto = WarehouseDTO::fromArray($request->validated());
        $warehouse = $this->warehouseService->store($restaurantId, $dto);

        return response()->json([
            'data'    => new WarehouseResource($warehouse),
            'message' => 'Tạo kho chứa thành công.',
        ], 201);
    }

    /**
     * PUT/PATCH /api/tenant/warehouses/{id}
     * Cập nhật kho chứa.
     */
    public function update(UpdateWarehouseRequest $request, string $id): JsonResponse
    {
        $dto = WarehouseDTO::fromArray($request->validated());
        $warehouse = $this->warehouseService->update($id, $dto);

        return response()->json([
            'data'    => new WarehouseResource($warehouse),
            'message' => 'Cập nhật kho chứa thành công.',
        ]);
    }

    /**
     * DELETE /api/tenant/warehouses/{id}
     * Xóa kho chứa. Không thể xóa kho mặc định.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $this->warehouseService->destroy($id);

        return response()->json([
            'message' => 'Xóa kho chứa thành công.',
        ], 200);
    }
}
