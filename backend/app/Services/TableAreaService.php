<?php

namespace App\Services;

use App\Contracts\Repositories\TableAreaRepositoryInterface;
use App\Contracts\Services\TableAreaServiceInterface;
use App\DTOs\TableAreaDTO;

/**
 * TableAreaService — xử lý business logic cho khu vực bàn.
 *
 * SRP: Service chỉ chứa logic nghiệp vụ.
 * DIP: Inject repository qua interface, không phụ thuộc implementation.
 */
class TableAreaService implements TableAreaServiceInterface
{
    public function __construct(
        protected TableAreaRepositoryInterface $tableAreaRepository
    ) {}

    public function getAllAreas(string $restaurantId)
    {
        return $this->tableAreaRepository->getByRestaurantId($restaurantId);
    }

    public function getAreaById(string $id, string $restaurantId)
    {
        return $this->tableAreaRepository->findByIdAndRestaurantId($id, $restaurantId);
    }

    public function createArea(string $restaurantId, TableAreaDTO $dto)
    {
        $data = $dto->toArray();
        $data['restaurant_id'] = $restaurantId;

        return $this->tableAreaRepository->create($data);
    }

    /**
     * Cập nhật khu vực bàn — chỉ update các field thật sự được gửi lên.
     *
     * Nhận raw array thay vì DTO để tránh DTO gán default cho field không gửi,
     * gây reset dữ liệu khi PATCH chỉ gửi 1-2 field.
     *
     * @param array<string, mixed> $data Chỉ chứa field client gửi lên (validated)
     */
    public function updateArea(string $id, string $restaurantId, array $data)
    {
        $area = $this->getAreaById($id, $restaurantId); // Đảm bảo ownership

        // Chỉ lấy các field được phép update, bỏ qua restaurant_id
        $fillable = array_intersect_key($data, array_flip(['name', 'description', 'display_order']));

        return $this->tableAreaRepository->update($area, $fillable);
    }

    public function deleteArea(string $id, string $restaurantId): bool
    {
        $area = $this->getAreaById($id, $restaurantId);
        // Khi xóa area, migration đã set area_id = null cho các bàn thuộc area này
        return $this->tableAreaRepository->delete($area);
    }
}
