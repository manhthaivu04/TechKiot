<?php

namespace App\Http\Controllers\Api\Public;

use App\DTOs\GetMenuDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\GetMenuRequest;
use App\Services\Menu\MenuService;
use Illuminate\Http\JsonResponse;

/**
 * QrMenuController — Điểm vào duy nhất cho API lấy Menu qua QR.
 *
 * ── Tương đồng với OrderController ──────────────────────────────────────────
 * OrderController::store(PlaceOrderRequest)
 *   → PlaceOrderDTO::fromArray() → OrderService::placeOrder() → JSON
 *
 * QrMenuController::index(GetMenuRequest)
 *   → GetMenuDTO::fromArray()    → MenuService::getMenu()    → JSON
 *
 * Nguyên tắc SRP: Controller CHỈ làm 3 việc:
 * 1. Nhận Request đã validate (FormRequest lo việc này).
 * 2. Map sang DTO.
 * 3. Gọi Service và trả JSON response.
 *
 * Không chứa query DB, business logic hay thuật toán format nào.
 */
class QrMenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    /**
     * GET /api/public/menu
     *
     * Query params:
     *   - public_token: UUID (restaurant_id cho QR tĩnh, table_id cho QR bàn Pro)
     *   - type:         string (qr_static | qr_table)
     *
     * @param  GetMenuRequest $request
     * @return JsonResponse
     */
    public function index(GetMenuRequest $request): JsonResponse
    {
        // 1. Map validated data → DTO (type-safe, không truyền raw array vào Service)
        $dto = GetMenuDTO::fromArray($request->validated());

        // 2. Service chọn Strategy và trả về menu đã gom nhóm
        $menu = $this->menuService->getMenu($dto);

        // 3. Trả về JSON chuẩn
        return response()->json([
            'success' => true,
            'data'    => $menu,
        ]);
    }
}
