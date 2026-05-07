<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository extends BaseEloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function emailExists(string $email, ?string $ignoreUserId = null): bool
    {
        /** @var User $queryModel */
        $queryModel = $this->model;

        $query = $queryModel->newQuery()->where('email', $email);

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}

