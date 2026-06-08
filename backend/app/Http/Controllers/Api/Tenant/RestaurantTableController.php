<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRestaurantTableRequest;
use App\Http\Requests\Tenant\UpdateRestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Contracts\Services\RestaurantTableServiceInterface;
use App\DTOs\RestaurantTableDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RestaurantTableController — CRUD bàn ăn.
 *
 * SRP: Controller chỉ nhận request, chuyển sang DTO, gọi service, trả response.
 * DIP: Inject RestaurantTableServiceInterface.
 *
 * Luồng: Request → FormRequest (validate) → Controller → DTO → Service → Repository → Model
 *
 * OCP: Sau này mở rộng QR order, sơ đồ bàn chỉ cần thêm method mới
 * hoặc controller mới — không cần sửa logic hiện tại.
 */
class RestaurantTableController extends Controller
{
    public function __construct(
        protected RestaurantTableServiceInterface $tableService
    ) {}

    /**
     * GET /api/tenant/restaurant-tables
     * Lấy danh sách bàn, hỗ trợ filter: area_id, status, search.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $filters = $request->only(['area_id', 'status', 'search', 'per_page']);

        $tables = $this->tableService->getAllTables($restaurantId, $filters);

        return response()->json(
            RestaurantTableResource::collection($tables)->response()->getData(true)
        );
    }

    /**
     * POST /api/tenant/restaurant-tables
     * Tạo bàn mới. Nếu không truyền uid, hệ thống tự sinh B-001, B-002...
     */
    public function store(StoreRestaurantTableRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $dto = RestaurantTableDTO::fromArray($request->validated());
        $table = $this->tableService->createTable($restaurantId, $dto);

        return response()->json([
            'data'    => new RestaurantTableResource($table->load('area')),
            'message' => 'Tạo bàn ăn thành công.',
        ], 201);
    }

    /**
     * GET /api/tenant/restaurant-tables/{id}
     * Xem chi tiết bàn.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $table = $this->tableService->getTableById($id, $restaurantId);

        return response()->json([
            'data' => new RestaurantTableResource($table),
        ]);
    }

    /**
     * PUT/PATCH /api/tenant/restaurant-tables/{id}
     * Cập nhật bàn ăn.
     */
    public function update(UpdateRestaurantTableRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $table = $this->tableService->updateTable($id, $restaurantId, $request->validated());

        return response()->json([
            'data'    => new RestaurantTableResource($table->load('area')),
            'message' => 'Cập nhật bàn ăn thành công.',
        ]);
    }

    /**
     * DELETE /api/tenant/restaurant-tables/{id}
     * Xóa bàn ăn.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $this->tableService->deleteTable($id, $restaurantId);

        return response()->json([
            'message' => 'Xóa bàn ăn thành công.',
        ], 200);
    }
}
