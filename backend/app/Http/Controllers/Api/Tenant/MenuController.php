<?php

namespace App\Http\Controllers\Api\Tenant;

use App\DTOs\GetMenuDTO;
use App\Http\Controllers\Controller;
use App\Services\Menu\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MenuController — Menu gọi món cho nhân viên tenant (POS / thu ngân).
 */
class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    /**
     * GET /api/tenant/menu
     *
     * Trả menu đã gom nhóm, chỉ gồm MENU_ITEM active và còn hàng.
     */
    public function index(Request $request): JsonResponse
    {
        $dto = GetMenuDTO::forTenant($request->user()->restaurant_id);

        return response()->json([
            'success' => true,
            'data'    => $this->menuService->getMenu($dto),
        ]);
    }
}
