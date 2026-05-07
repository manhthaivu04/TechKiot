<?php

namespace App\Contracts\Repositories;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function emailExists(string $email, ?string $ignoreUserId = null): bool;
}

