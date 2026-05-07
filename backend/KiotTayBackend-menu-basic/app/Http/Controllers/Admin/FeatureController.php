<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\FeatureServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeatureRequest;
use App\Http\Requests\Admin\UpdateFeatureRequest;
use App\Http\Resources\FeatureResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeatureController extends Controller
{
    public function __construct(
        private readonly FeatureServiceInterface $service,
    ) {}

    /** GET /api/admin/features */
    public function index(Request $request): AnonymousResourceCollection
    {
        $features = $this->service->list(
            filters: $request->only(['is_active', 'search']),
            perPage: (int) $request->get('per_page', 50),
        );

        return FeatureResource::collection($features);
    }

    /** POST /api/admin/features */
    public function store(StoreFeatureRequest $request): JsonResponse
    {
        $feature = $this->service->create($request->validated());

        return response()->json(new FeatureResource($feature), 201);
    }

    /** GET /api/admin/features/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json(new FeatureResource($this->service->findOrFail($id)));
    }

    /** PUT /api/admin/features/{id} */
    public function update(UpdateFeatureRequest $request, string $id): JsonResponse
    {
        $feature = $this->service->update($id, $request->validated());

        return response()->json(new FeatureResource($feature));
    }

    /** PATCH /api/admin/features/{id}/toggle */
    public function toggle(string $id): JsonResponse
    {
        $feature = $this->service->toggle($id);

        return response()->json([
            'message' => 'Feature status toggled.',
            'feature' => new FeatureResource($feature),
        ]);
    }
}
