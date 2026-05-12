<?php

namespace App\Contracts\Repositories;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function emailExists(string $email, ?string $ignoreUserId = null): bool;

    /**
     * Paginate users belonging to a restaurant.
     *
     * @param array<string, mixed> $filters
     */
    public function getByRestaurantId(string $restaurantId, array $filters = []);

    public function findByIdAndRestaurantId(string $id, string $restaurantId);
}

