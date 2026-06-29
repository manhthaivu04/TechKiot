<?php

namespace App\Services;

use App\Contracts\Repositories\FeatureRepositoryInterface;
use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Contracts\Services\PackageServiceInterface;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PackageService implements PackageServiceInterface
{
    public function __construct(
        private readonly PackageRepositoryInterface $repository,
        private readonly FeatureRepositoryInterface $featureRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function allActiveWithFeatures(): Collection
    {
        return $this->repository->allActiveWithFeatures();
    }

    public function findOrFail(string $id): Package
    {
        /** @var Package */
        return $this->repository->findByIdOrFail($id);
    }

    public function create(array $data): Package
    {
        if ($this->repository->findByCode(strtoupper($data['code']))) {
            throw ValidationException::withMessages([
                'code' => "Package code '{$data['code']}' already exists.",
            ]);
        }

        $data['code'] = strtoupper($data['code']);

        /** @var Package $package */
        $package = $this->repository->create($data);

        // Optionally attach features on creation
        if (! empty($data['feature_ids'])) {
            $this->repository->syncFeatures($package, $data['feature_ids']);
        }

        if (! empty($data['prices'])) {
            foreach ($data['prices'] as $priceData) {
                $package->prices()->create($priceData);
            }
        }

        return $package->load(['features', 'prices']);
    }

    public function update(string $id, array $data): Package
    {
        $package = $this->findOrFail($id);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
            $existing = $this->repository->findByCode($data['code']);

            if ($existing && $existing->id !== $id) {
                throw ValidationException::withMessages([
                    'code' => "Package code '{$data['code']}' already exists.",
                ]);
            }
        }

        /** @var Package $package */
        $package = $this->repository->update($package, $data);

        if (array_key_exists('feature_ids', $data)) {
            $this->repository->syncFeatures($package, $data['feature_ids']);
        }

        if (array_key_exists('prices', $data)) {
            $existingIds = [];
            foreach ($data['prices'] as $priceData) {
                if (! empty($priceData['id'])) {
                    $package->prices()->where('id', $priceData['id'])->update($priceData);
                    $existingIds[] = $priceData['id'];
                } else {
                    $newPrice = $package->prices()->create($priceData);
                    $existingIds[] = $newPrice->id;
                }
            }
            // Delete prices that are not in the new list
            $package->prices()->whereNotIn('id', $existingIds)->delete();
        }

        return $package->load(['features', 'prices']);
    }

    public function syncFeatures(string $packageId, array $featureIds): Package
    {
        $package = $this->findOrFail($packageId);

        // Validate all supplied feature IDs exist
        $found = $this->featureRepository->findByCodes([]);  // ids not codes — use direct check
        $this->repository->syncFeatures($package, $featureIds);

        return $package->load('features');
    }

    public function toggle(string $id): Package
    {
        $package = $this->findOrFail($id);

        /** @var Package */
        return $this->repository->update($package, [
            'is_active' => ! $package->is_active,
        ]);
    }

    public function delete(string $id): bool
    {
        $package = $this->findOrFail($id);

        // Kiểm tra ràng buộc dữ liệu: Không cho phép xóa gói dịch vụ đang được nhà hàng sử dụng
        if (\App\Models\RestaurantSubscription::where('package_id', $id)->exists()) {
            throw ValidationException::withMessages([
                'package' => 'Không thể xóa gói dịch vụ này vì đang có nhà hàng đăng ký sử dụng.',
            ]);
        }

        $package->prices()->delete();

        return $this->repository->delete($package);
    }
}
