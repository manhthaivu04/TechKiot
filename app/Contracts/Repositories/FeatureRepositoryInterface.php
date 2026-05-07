<?php

namespace App\Contracts\Repositories;

use App\Models\Feature;
use Illuminate\Support\Collection;

interface FeatureRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code): ?Feature;

    /** @return Collection<int, Feature> */
    public function allActive(): Collection;

    /** @param list<string> $codes */
    public function findByCodes(array $codes): Collection;
}
