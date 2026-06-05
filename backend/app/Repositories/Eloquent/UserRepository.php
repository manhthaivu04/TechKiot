<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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

    public function getByRestaurantId(string $restaurantId, array $filters = [])
    {
        /** @var Builder $query */
        $query = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId);

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $query->where(function (Builder $sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest()->paginate($perPage);
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();
    }
}

