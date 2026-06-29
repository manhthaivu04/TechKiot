<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PackageRepository extends BaseEloquentRepository implements PackageRepositoryInterface
{
    public function __construct(Package $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array<string, mixed> $filters  Supports: is_active, search
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['features', 'prices'])
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('code', 'like', "%{$filters['search']}%");
                })
            )
            ->orderBy('price')
            ->paginate($perPage);
    }

    public function findByCode(string $code): ?Package
    {
        return $this->model->newQuery()
            ->with(['features', 'prices'])
            ->where('code', $code)
            ->first();
    }

    public function allActiveWithFeatures(): Collection
    {
        return $this->model->newQuery()
            ->with(['features', 'prices'])
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    public function syncFeatures(Package $package, array $featureIds): void
    {
        $package->features()->sync($featureIds);
    }
}
