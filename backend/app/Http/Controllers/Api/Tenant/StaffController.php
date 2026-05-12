<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Services\StaffServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreStaffRequest;
use App\Http\Requests\Tenant\UpdateStaffRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffServiceInterface $staffService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $filters = $request->only(['q', 'role', 'is_active', 'per_page']);

        $staff = $this->staffService->paginate($restaurantId, $filters);

        return response()->json([
            'data' => UserResource::collection($staff->items()),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page'    => $staff->lastPage(),
                'total'        => $staff->total(),
            ],
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $user = $this->staffService->create($restaurantId, $request->validated());

        return response()->json([
            'message' => 'Staff user created successfully.',
            'data'    => new UserResource($user),
        ], 201);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $user = $this->staffService->find($restaurantId, $id);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateStaffRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $user = $this->staffService->update($restaurantId, $id, $request->validated());

        return response()->json([
            'message' => 'Staff user updated successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $actorUserId = $request->user()->id;

        $user = $this->staffService->deactivate($restaurantId, $id, $actorUserId);

        return response()->json([
            'message' => 'Staff user deactivated successfully.',
            'data'    => new UserResource($user),
        ]);
    }
}

