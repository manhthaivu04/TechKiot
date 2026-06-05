<?php

namespace App\Contracts\Services;

use App\Models\User;

interface StaffServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(string $restaurantId, array $filters = []);

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $restaurantId, array $data): User;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $restaurantId, string $staffId, array $data): User;

    public function deactivate(string $restaurantId, string $staffId, string $actorUserId): User;

    public function find(string $restaurantId, string $staffId): User;
}

