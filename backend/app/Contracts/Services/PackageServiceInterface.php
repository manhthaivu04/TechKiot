<?php

namespace App\Contracts\Services;

use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PackageServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function allActiveWithFeatures(): Collection;

    public function findOrFail(string $id): Package;

    public function create(array $data): Package;

    public function update(string $id, array $data): Package;

    /**
     * Replace the full feature list for a package.
     * @param list<string> $featureIds
     */
    public function syncFeatures(string $packageId, array $featureIds): Package;

    public function toggle(string $id): Package;

    public function delete(string $id): bool;
}
