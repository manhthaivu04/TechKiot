<?php

namespace App\Services;

use App\Contracts\Repositories\ItemGroupRepositoryInterface;
use App\Contracts\Services\ItemGroupServiceInterface;
use Illuminate\Support\Facades\DB;

class ItemGroupService implements ItemGroupServiceInterface
{
    public function __construct(
        protected ItemGroupRepositoryInterface $itemGroupRepository
    ) {}

    public function getAllGroups(string $restaurantId)
    {
        return $this->itemGroupRepository->getByRestaurantId($restaurantId);
    }

    public function getGroupById(string $id, string $restaurantId)
    {
        return $this->itemGroupRepository->findByIdAndRestaurantId($id, $restaurantId);
    }

    public function createGroup(string $restaurantId, array $data)
    {
        $data['restaurant_id'] = $restaurantId;
        return $this->itemGroupRepository->create($data);
    }

    public function updateGroup(string $id, string $restaurantId, array $data)
    {
        $group = $this->getGroupById($id, $restaurantId); // Ensure ownership
        return $this->itemGroupRepository->update($group, $data);
    }

    public function deleteGroup(string $id, string $restaurantId)
    {
        $group = $this->getGroupById($id, $restaurantId);
        // Có thể thêm logic check xem group có item nào không trước khi xóa
        return $this->itemGroupRepository->delete($group);
    }
}
