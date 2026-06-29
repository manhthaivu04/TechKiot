<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\PackageServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\SyncPackageFeaturesRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageServiceInterface $service,
    ) {}

    /** GET /api/admin/packages */
    public function index(Request $request): AnonymousResourceCollection
    {
        $packages = $this->service->list(
            filters: $request->only(['is_active', 'search']),
            perPage: (int) $request->get('per_page', 15),
        );

        return PackageResource::collection($packages);
    }

    /** POST /api/admin/packages */
    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = $this->service->create($request->validated());

        return response()->json(new PackageResource($package), 201);
    }

    /** GET /api/admin/packages/{id} */
    public function show(string $id): JsonResponse
    {
        $package = $this->service->findOrFail($id);

        return response()->json(new PackageResource($package->load(['features', 'prices'])));
    }

    /** PUT /api/admin/packages/{id} */
    public function update(UpdatePackageRequest $request, string $id): JsonResponse
    {
        $package = $this->service->update($id, $request->validated());

        return response()->json(new PackageResource($package));
    }

    /** PATCH /api/admin/packages/{id}/toggle */
    public function toggle(string $id): JsonResponse
    {
        $package = $this->service->toggle($id);

        return response()->json([
            'message' => 'Package status toggled.',
            'package' => new PackageResource($package->load(['features', 'prices'])),
        ]);
    }

    /** PUT /api/admin/packages/{id}/features */
    public function syncFeatures(SyncPackageFeaturesRequest $request, string $id): JsonResponse
    {
        $package = $this->service->syncFeatures($id, $request->validated('feature_ids'));

        return response()->json([
            'message' => 'Features synced successfully.',
            'package' => new PackageResource($package->load(['features', 'prices'])),
        ]);
    }

    /** DELETE /api/admin/packages/{id} */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Package deleted successfully.'], 204);
    }
}
