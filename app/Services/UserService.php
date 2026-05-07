<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function createOwner(array $data): User
    {
        if ($this->repository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $payload = [
            'restaurant_id' => $data['restaurant_id'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => $data['password'],
            'role'          => UserRole::OWNER->value,
            'is_active'     => $data['is_active'] ?? true,
        ];

        /** @var User */
        return $this->repository->create($payload);
    }
}

