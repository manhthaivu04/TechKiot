<?php

namespace App\Services;

use App\Contracts\Repositories\FeatureRepositoryInterface;
use App\Contracts\Services\FeatureServiceInterface;
use App\Models\Feature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FeatureService implements FeatureServiceInterface
{
    public function __construct(
        private readonly FeatureRepositoryInterface $repository,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    public function findOrFail(string $id): Feature
    {
        /** @var Feature */
        return $this->repository->findByIdOrFail($id);
    }

    public function create(array $data): Feature
    {
        // Ensure the code is unique
        if ($this->repository->findByCode($data['code'])) {
            throw ValidationException::withMessages([
                'code' => "Feature code '{$data['code']}' already exists.",
            ]);
        }

        $data['code'] = strtoupper($data['code']);

        /** @var Feature */
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Feature
    {
        $feature = $this->findOrFail($id);

        // If code is being changed, check uniqueness
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
            $existing = $this->repository->findByCode($data['code']);

            if ($existing && $existing->id !== $id) {
                throw ValidationException::withMessages([
                    'code' => "Feature code '{$data['code']}' already exists.",
                ]);
            }
        }

        /** @var Feature */
        return $this->repository->update($feature, $data);
    }

    public function toggle(string $id): Feature
    {
        $feature = $this->findOrFail($id);

        /** @var Feature */
        return $this->repository->update($feature, [
            'is_active' => ! $feature->is_active,
        ]);
    }
}
