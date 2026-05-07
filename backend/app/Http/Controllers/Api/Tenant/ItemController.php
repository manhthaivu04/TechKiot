<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreItemRequest;
use App\Http\Requests\Tenant\UpdateItemRequest;
use App\Contracts\Services\ItemServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        protected ItemServiceInterface $itemService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $filters = $request->only(['item_group_id', 'item_type', 'per_page']);
        
        $items = $this->itemService->getAllItems($restaurantId, $filters);
        
        return response()->json($items); // Pagination returns standard format
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $data = $request->validated();
        $image = $request->file('image');

        $item = $this->itemService->createItem($restaurantId, $data, $image);
        
        return response()->json(['data' => $item, 'message' => 'Item created successfully'], 201);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $item = $this->itemService->getItemById($id, $restaurantId);
        $item->load('itemGroup');
        
        return response()->json(['data' => $item]);
    }

    public function update(UpdateItemRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $data = $request->validated();
        $image = $request->file('image');

        $item = $this->itemService->updateItem($id, $restaurantId, $data, $image);
        
        return response()->json(['data' => $item, 'message' => 'Item updated successfully']);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $this->itemService->deleteItem($id, $restaurantId);
        
        return response()->json(['message' => 'Item deleted successfully'], 204);
    }
}
