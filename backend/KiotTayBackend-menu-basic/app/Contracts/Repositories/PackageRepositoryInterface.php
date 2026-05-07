<?php

namespace App\Contracts\Repositories;

use App\Models\Package;
use Illuminate\Support\Collection;

interface PackageRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code): ?Package;

    /** @return Collection<int, Package> active packages with their features */
    public function allActiveWithFeatures(): Collection;

    /**
     * Sync the feature list for a given package.
     * @param list<string> $featureIds
     */
    public function syncFeatures(Package $package, array $featureIds): void;
}
