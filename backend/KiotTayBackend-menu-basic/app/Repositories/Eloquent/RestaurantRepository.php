<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RestaurantRepositoryInterface;
use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RestaurantRepository extends BaseEloquentRepository implements RestaurantRepositoryInterface
{
    public function __construct(Restaurant $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array<string, mixed> $filters  Supports: status, search
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('activeSubscription.package')
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('phone', 'like', "%{$filters['search']}%");
                })
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->get();
    }

    public function findWithActiveSubscription(string $id): ?Restaurant
    {
        return $this->model->newQuery()
            ->with(['activeSubscription.package.features'])
            ->find($id);
    }

    public function tokenExists(string $token, ?string $excludeId = null): bool
    {
        return $this->model->newQuery()
            ->where('public_order_token', $token)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
