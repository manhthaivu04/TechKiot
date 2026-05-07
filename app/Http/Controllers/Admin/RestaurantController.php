<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\RestaurantServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OnboardRestaurantRequest;
use App\Http\Requests\Admin\StoreRestaurantRequest;
use App\Http\Requests\Admin\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin\RestaurantController
 *
 * Thin controller: validates (via FormRequest), delegates to Service, returns Resource.
 * No business logic lives here — satisfying SRP.
 */
class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantServiceInterface $service,
    ) {}

    /** GET /api/admin/restaurants */
    public function index(Request $request): AnonymousResourceCollection
    {
        $restaurants = $this->service->list(
            filters: $request->only(['status', 'search']),
            perPage: (int) $request->get('per_page', 15),
        );

        return RestaurantResource::collection($restaurants);
    }

    /** POST /api/admin/restaurants */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $restaurant = $this->service->create($request->validated());

        return response()->json(new RestaurantResource($restaurant), 201);
    }

    /** POST /api/admin/restaurants/onboard */
    public function onboard(OnboardRestaurantRequest $request): JsonResponse
    {
        $result = $this->service->onboard($request->validated());

        $restaurant = $result['restaurant']->load('activeSubscription.package');

        return response()->json([
            'message'      => 'Restaurant onboarded successfully.',
            'restaurant'   => new RestaurantResource($restaurant),
            'subscription' => new SubscriptionResource($result['subscription']->load('package')),
            'owner'        => new UserResource($result['owner']),
        ], 201);
    }

    /** GET /api/admin/restaurants/{id} */
    public function show(string $id): JsonResponse
    {
        $restaurant = $this->service->findOrFail($id);

        return response()->json(new RestaurantResource($restaurant->load('activeSubscription.package')));
    }

    /** PUT /api/admin/restaurants/{id} */
    public function update(UpdateRestaurantRequest $request, string $id): JsonResponse
    {
        $restaurant = $this->service->update($id, $request->validated());

        return response()->json(new RestaurantResource($restaurant));
    }

    /** PATCH /api/admin/restaurants/{id}/lock */
    public function lock(string $id): JsonResponse
    {
        $restaurant = $this->service->lock($id);

        return response()->json([
            'message'    => 'Restaurant has been suspended.',
            'restaurant' => new RestaurantResource($restaurant),
        ]);
    }

    /** PATCH /api/admin/restaurants/{id}/unlock */
    public function unlock(string $id): JsonResponse
    {
        $restaurant = $this->service->unlock($id);

        return response()->json([
            'message'    => 'Restaurant has been reactivated.',
            'restaurant' => new RestaurantResource($restaurant),
        ]);
    }
}
