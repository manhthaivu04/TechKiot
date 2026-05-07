<?php

namespace App\Contracts\Services;

use App\Models\Feature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FeatureServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function allActive(): Collection;

    public function findOrFail(string $id): Feature;

    public function create(array $data): Feature;

    public function update(string $id, array $data): Feature;

    public function toggle(string $id): Feature;
}
