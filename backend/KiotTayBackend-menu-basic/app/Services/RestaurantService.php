<?php

namespace App\Services;

use App\Contracts\Repositories\RestaurantRepositoryInterface;
use App\Contracts\Services\SubscriptionServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Services\RestaurantServiceInterface;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * RestaurantService — pure business logic, no HTTP awareness.
 *
 * Depends on the Repository INTERFACE (DIP), not the Eloquent class directly.
 * Adding a new operation never modifies this class unnecessarily (OCP).
 */
class RestaurantService implements RestaurantServiceInterface
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $repository,
        private readonly SubscriptionServiceInterface  $subscriptionService,
        private readonly UserServiceInterface          $userService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findOrFail(string $id): Restaurant
    {
        /** @var Restaurant */
        return $this->repository->findByIdOrFail($id);
    }

    public function create(array $data): Restaurant
    {
        // Auto-generate a unique public_order_token for QR Static
        $data['public_order_token'] = $this->generateUniqueToken();

        /** @var Restaurant */
        return $this->repository->create($data);
    }

    public function onboard(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $restaurantPayload = $data['restaurant'];
            $restaurantPayload['public_order_token'] = $this->generateUniqueToken();

            /** @var Restaurant $restaurant */
            $restaurant = $this->repository->create($restaurantPayload);

            $subscription = $this->subscriptionService->assign(
                restaurantId: $restaurant->id,
                packageId: $data['package_id'],
            );

            $owner = $this->userService->createOwner([
                'restaurant_id' => $restaurant->id,
                'name'          => $data['owner']['name'],
                'email'         => $data['owner']['email'],
                'password'      => $data['owner']['password'],
                'is_active'     => $data['owner']['is_active'] ?? true,
            ]);

            return [
                'restaurant'   => $restaurant,
                'subscription' => $subscription,
                'owner'        => $owner,
            ];
        });
    }

    public function update(string $id, array $data): Restaurant
    {
        $restaurant = $this->findOrFail($id);

        // Prevent changing the token to a duplicate
        if (isset($data['public_order_token'])) {
            if ($this->repository->tokenExists($data['public_order_token'], $id)) {
                throw ValidationException::withMessages([
                    'public_order_token' => 'Token already in use by another restaurant.',
                ]);
            }
        }

        /** @var Restaurant */
        return $this->repository->update($restaurant, $data);
    }

    public function lock(string $id): Restaurant
    {
        $restaurant = $this->findOrFail($id);

        /** @var Restaurant */
        return $this->repository->update($restaurant, [
            'status' => RestaurantStatus::SUSPENDED->value,
        ]);
    }

    public function unlock(string $id): Restaurant
    {
        $restaurant = $this->findOrFail($id);

        /** @var Restaurant */
        return $this->repository->update($restaurant, [
            'status' => RestaurantStatus::ACTIVE->value,
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while ($this->repository->tokenExists($token));

        return $token;
    }
}
