<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\SubscriptionServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $service,
    ) {}

    /** GET /api/admin/restaurants/{restaurantId}/subscriptions */
    public function index(string $restaurantId): JsonResponse
    {
        $subscriptions = $this->service->listForRestaurant($restaurantId);

        return response()->json(SubscriptionResource::collection($subscriptions));
    }

    /** GET /api/admin/restaurants/{restaurantId}/subscriptions/active */
    public function active(string $restaurantId): JsonResponse
    {
        $subscription = $this->service->activeForRestaurant($restaurantId);

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription found.'], 404);
        }

        return response()->json(new SubscriptionResource($subscription));
    }

    /** POST /api/admin/restaurants/{restaurantId}/subscriptions */
    public function assign(AssignSubscriptionRequest $request, string $restaurantId): JsonResponse
    {
        $subscription = $this->service->assign($restaurantId, $request->validated('package_id'));

        return response()->json([
            'message'      => 'Package assigned successfully.',
            'subscription' => new SubscriptionResource($subscription->load('package')),
        ], 201);
    }

    /** PATCH /api/admin/subscriptions/{id}/cancel */
    public function cancel(string $id): JsonResponse
    {
        $subscription = $this->service->cancel($id);

        return response()->json([
            'message'      => 'Subscription cancelled.',
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }
}
