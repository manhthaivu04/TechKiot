<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\UserServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOwnerRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class OwnerUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $service,
    ) {}

    /** POST /api/admin/owners */
    public function store(StoreOwnerRequest $request): JsonResponse
    {
        $user = $this->service->createOwner($request->validated());

        return response()->json([
            'message' => 'Owner user created successfully.',
            'user'    => new UserResource($user),
        ], 201);
    }
}

