<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\StockDocumentRepositoryInterface;
use App\Models\StockDocument;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Eloquent implementation của StockDocumentRepositoryInterface.
 *
 * SRP: class này CHỈ truy vấn database — không chứa business logic.
 */
class StockDocumentRepository extends BaseEloquentRepository implements StockDocumentRepositoryInterface
{
    public function __construct(StockDocument $model)
    {
        parent::__construct($model);
    }

    public function getByRestaurantId(string $restaurantId): Collection
    {
        return $this->model
            ->where('restaurant_id', $restaurantId)
            ->with(['warehouse', 'createdBy', 'stockDocumentItems.item'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Sinh mã chứng từ tự động.
     *
     * Format: {prefix}{number} — VD: PN001, PX012.
     * Tìm mã cuối cùng cùng prefix trong restaurant, tăng số thứ tự lên 1.
     */
    public function generateCode(string $restaurantId, string $prefix): string
    {
        $lastDocument = $this->model
            ->where('restaurant_id', $restaurantId)
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        if ($lastDocument) {
            // Lấy phần số cuối của mã (VD: "PN012" → 12)
            $lastNumber = (int) substr($lastDocument->code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
