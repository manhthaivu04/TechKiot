<?php

namespace App\Contracts\Services;

use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RestaurantServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $id): Restaurant;

    public function create(array $data): Restaurant;

    /**
     * Create restaurant, assign a package, and create an OWNER user (admin onboarding).
     *
     * @param array{
     *   restaurant: array<string, mixed>,
     *   package_id: string,
     *   owner: array{name: string, email: string, password: string, is_active?: bool}
     * } $data
     *
     * @return array{restaurant: Restaurant, owner: \App\Models\User, subscription: \App\Models\RestaurantSubscription}
     */
    public function onboard(array $data): array;

    public function update(string $id, array $data): Restaurant;

    public function lock(string $id): Restaurant;

    public function unlock(string $id): Restaurant;
}
