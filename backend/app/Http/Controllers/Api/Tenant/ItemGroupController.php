<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreItemGroupRequest;
use App\Http\Requests\Tenant\UpdateItemGroupRequest;
use App\Contracts\Services\ItemGroupServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemGroupController extends Controller
{
    public function __construct(
        protected ItemGroupServiceInterface $itemGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $groups = $this->itemGroupService->getAllGroups($restaurantId);
        
        return response()->json(['data' => $groups]);
    }

    public function store(StoreItemGroupRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $group = $this->itemGroupService->createGroup($restaurantId, $request->validated());
        
        return response()->json(['data' => $group, 'message' => 'Group created successfully'], 201);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $group = $this->itemGroupService->getGroupById($id, $restaurantId);
        
        return response()->json(['data' => $group]);
    }

    public function update(UpdateItemGroupRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $group = $this->itemGroupService->updateGroup($id, $restaurantId, $request->validated());
        
        return response()->json(['data' => $group, 'message' => 'Group updated successfully']);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $this->itemGroupService->deleteGroup($id, $restaurantId);
        
        return response()->json(['message' => 'Group deleted successfully'], 204);
    }
}
