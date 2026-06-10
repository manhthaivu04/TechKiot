<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RestaurantTableRepositoryInterface;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Eloquent implementation của RestaurantTableRepositoryInterface.
 *
 * SRP: class này CHỈ truy vấn database.
 * Mọi query đều scope theo restaurant_id (tenant isolation).
 *
 * Hỗ trợ filter: area_id, status, search (theo name/uid).
 */
class RestaurantTableRepository extends BaseEloquentRepository implements RestaurantTableRepositoryInterface
{
    public function __construct(RestaurantTable $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId, array $filters = [])
    {
        $query = $this->model
            ->where('restaurant_id', $restaurantId)
            ->with('area');

        // Filter theo khu vực
        if (!empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }

        // Filter theo trạng thái
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tìm kiếm theo tên hoặc uid
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('uid')->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model
            ->where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->with('area')
            ->firstOrFail();
    }

    /**
     * Lấy uid cuối cùng theo pattern B-xxx để service tính uid tiếp theo.
     * Sắp xếp giảm dần theo uid để lấy giá trị lớn nhất.
     */
    public function getLastUid(string $restaurantId): ?string
    {
        return $this->model
            ->where('restaurant_id', $restaurantId)
            ->where('uid', 'like', 'B-%')
            ->orderByRaw("CAST(SUBSTRING(uid, 3) AS UNSIGNED) DESC")
            ->value('uid');
    }

    /**
     * Kiểm tra uid đã tồn tại trong nhà hàng chưa.
     * Hỗ trợ excludeId cho trường hợp update (không tính chính nó).
     */
    public function uidExists(string $restaurantId, string $uid, ?string $excludeId = null): bool
    {
        $query = $this->model
            ->where('restaurant_id', $restaurantId)
            ->where('uid', $uid);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
