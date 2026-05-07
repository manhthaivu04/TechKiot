<?php

namespace App\Contracts\Services;

use App\Models\User;

interface UserServiceInterface
{
    /**
     * Create a restaurant OWNER user (created by SUPER_ADMIN).
     *
     * @param array<string, mixed> $data
     */
    public function createOwner(array $data): User;
}

