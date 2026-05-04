<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository contract.
 * Defines the primitive CRUD operations every repository must expose.
 */
interface BaseRepositoryInterface
{
    public function findById(string $id): ?Model;

    public function findByIdOrFail(string $id): Model;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data): Model;

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
