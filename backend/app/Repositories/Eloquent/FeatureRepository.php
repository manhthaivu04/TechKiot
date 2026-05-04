<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\FeatureRepositoryInterface;
use App\Models\Feature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FeatureRepository extends BaseEloquentRepository implements FeatureRepositoryInterface
{
    public function __construct(Feature $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array<string, mixed> $filters  Supports: is_active, search
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('code', 'like', "%{$filters['search']}%");
                })
            )
            ->orderBy('code')
            ->paginate($perPage);
    }

    public function findByCode(string $code): ?Feature
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function findByCodes(array $codes): Collection
    {
        return $this->model->newQuery()
            ->whereIn('code', $codes)
            ->get();
    }
}
