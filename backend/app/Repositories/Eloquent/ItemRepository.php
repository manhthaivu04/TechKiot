<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Enums\ItemAvailabilityStatus;
use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Eloquent implementation của ItemRepositoryInterface.
 *
 * Nguyên tắc SRP: class này CHỈ truy vấn cơ sở dữ liệu.
 * Không chứa business logic, format hay validation nào.
 */
class ItemRepository extends BaseEloquentRepository implements ItemRepositoryInterface
{
    public function __construct(Item $model)
    {
        parent::__construct($model);
    }

    // ── Owner/Admin queries ─────────────────────────────────────────────────

    public function getByRestaurantId(string $restaurantId, array $filters = [])
    {
        $query = $this->model->where('restaurant_id', $restaurantId)->with('itemGroup');

        if (isset($filters['item_group_id'])) {
            $query->where('item_group_id', $filters['item_group_id']);
        }

        if (isset($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdAndRestaurantId(string $id, string $restaurantId)
    {
        return $this->model->where('id', $id)
                           ->where('restaurant_id', $restaurantId)
                           ->firstOrFail();
    }

    // ── Public QR Menu query ─────────────────────────────────────────────────

    /**
     * Lấy items active cho luồng QR Menu — không phân trang.
     *
     * Eager-load `itemGroup` (filter active, sort display_order) để
     * Strategy/MenuGrouper có thể gom nhóm ngay mà không query thêm.
     *
     * {@inheritdoc}
     */
    public function getActiveMenuByRestaurantId(string $restaurantId): Collection
    {
        return $this->model
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('item_type', \App\Enums\ItemType::MENU_ITEM)
            ->where('availability_status', ItemAvailabilityStatus::IN_STOCK)
            ->with([
                'itemGroup' => fn ($q) => $q->where('is_active', true)
                                            ->orderBy('display_order'),
            ])
            ->orderBy('name')
            ->get();
    }
}
