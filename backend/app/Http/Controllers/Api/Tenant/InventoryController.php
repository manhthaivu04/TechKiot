<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Services\InventoryServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryServiceInterface $inventoryService
    ) {}

    /**
     * Lấy danh sách tồn kho hiện tại.
     * GET /api/tenant/inventory
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $filters = $request->only(['warehouse_id', 'search', 'per_page']);

        $inventory = $this->inventoryService->getInventory($restaurantId, $filters);

        return \App\Http\Resources\InventoryResource::collection($inventory)->response();
    }

    /**
     * Lấy danh sách lịch sử biến động sổ kho.
     * GET /api/tenant/inventory-transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $filters = $request->only(['warehouse_id', 'item_id', 'per_page']);

        $transactions = $this->inventoryService->getTransactions($restaurantId, $filters);

        return \App\Http\Resources\InventoryTransactionResource::collection($transactions)->response();
    }
}
