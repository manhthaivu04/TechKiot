<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract Eloquent base repository.
 *
 * Concrete repositories EXTEND this class and IMPLEMENT their own interface.
 * New storage backends (e.g. Redis cache layer) only need to implement the
 * same contract without touching service layer — satisfying the D principle.
 */
abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    public function __construct(protected readonly Model $model) {}

    public function findById(string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdOrFail(string $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * Override in child repos to add eager-loads or where clauses.
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
