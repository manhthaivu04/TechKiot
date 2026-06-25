<?php

namespace App\Services\Items\Creators;

use App\Contracts\Items\ItemCreatorInterface;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Models\Item;
use Illuminate\Http\UploadedFile;

/**
 * IngredientCreator — Tạo nguyên vật liệu dùng trong bếp (INGREDIENT).
 *
 * Trách nhiệm:
 *  - Không cần item_group_id (nhóm danh mục menu).
 *  - sale_price mặc định = 0 vì nguyên liệu không bán trực tiếp.
 *  - Upload ảnh lên Cloudinary (nếu người dùng cung cấp).
 *  - Lưu Item vào CSDL.
 *
 * Tương lai (khi mở gói Premium INVENTORY_MANAGEMENT):
 *  - Gọi InventoryService để tạo "Thẻ Kho" (Inventory Card) ban đầu.
 *  - Thiết lập ngưỡng cảnh báo tồn kho tối thiểu.
 */
class IngredientCreator implements ItemCreatorInterface
{
    public function __construct(
        protected ItemRepositoryInterface $itemRepository,
    ) {}

    public function create(string $restaurantId, array $data, ?UploadedFile $image = null): Item
    {
        $data['restaurant_id'] = $restaurantId;

        // Nguyên liệu không thuộc nhóm Menu và không có giá bán
        $data['item_group_id'] = null;
        $data['sale_price']    = 0;

        // Upload ảnh lên Cloudinary (nếu có)
        if ($image) {
            $data['image_url'] = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                'folder' => "kiottay/{$restaurantId}/items",
            ])['secure_url'];
        }

        $item = $this->itemRepository->create($data);

        // TODO (Gói Premium): Gọi InventoryService::createCardForIngredient($item)

        return $item;
    }
}
