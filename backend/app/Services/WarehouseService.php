<?php

namespace App\Services;

use App\Contracts\Repositories\WarehouseRepositoryInterface;
use App\Contracts\Services\WarehouseServiceInterface;
use App\DTOs\WarehouseDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * WarehouseService — xử lý business logic cho kho chứa.
 *
 * SRP: Service chỉ chứa logic nghiệp vụ.
 * DIP: Inject repository qua interface, không phụ thuộc implementation.
 */
class WarehouseService implements WarehouseServiceInterface
{
    public function __construct(
        protected WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function list(string $restaurantId): Collection
    {
        return $this->warehouseRepository->findByRestaurant($restaurantId);
    }

    /**
     * Tạo kho mới.
     * Nếu is_default = true → unset tất cả kho cũ của restaurant về false trước.
     * Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu.
     */
    public function store(string $restaurantId, WarehouseDTO $dto)
    {
        return DB::transaction(function () use ($restaurantId, $dto) {
            if ($dto->isDefault) {
                $this->warehouseRepository->unsetDefaultByRestaurant($restaurantId);
            }

            $data = $dto->toArray();
            $data['restaurant_id'] = $restaurantId;

            return $this->warehouseRepository->create($data);
        });
    }

    /**
     * Cập nhật kho.
     * Nếu is_default = true → unset tất cả kho cũ của restaurant về false trước.
     */
    public function update(string $warehouseId, WarehouseDTO $dto)
    {
        $warehouse = $this->warehouseRepository->findByIdOrFail($warehouseId);

        return DB::transaction(function () use ($warehouse, $dto) {
            if ($dto->isDefault) {
                $this->warehouseRepository->unsetDefaultByRestaurant($warehouse->restaurant_id);
            }

            return $this->warehouseRepository->update($warehouse, $dto->toArray());
        });
    }

    /**
     * Xóa kho.
     * Không được phép xóa kho mặc định — throw ConflictHttpException (409).
     */
    public function destroy(string $warehouseId): bool
    {
        $warehouse = $this->warehouseRepository->findByIdOrFail($warehouseId);

        if ($warehouse->is_default) {
            throw new ConflictHttpException('Không thể xóa kho mặc định. Hãy chuyển kho mặc định sang kho khác trước.');
        }

        return $this->warehouseRepository->delete($warehouse);
    }
}
