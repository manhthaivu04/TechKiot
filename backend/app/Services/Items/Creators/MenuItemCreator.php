<?php

namespace App\Services\Items\Creators;

use App\Contracts\Items\ItemCreatorInterface;
use App\Contracts\Repositories\ItemGroupRepositoryInterface;
use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Models\Item;
use Exception;
use Illuminate\Http\UploadedFile;

/**
 * MenuItemCreator — Tạo mặt hàng bán trực tiếp cho khách (MENU_ITEM).
 *
 * Trách nhiệm:
 *  - Validate item_group_id thuộc đúng nhà hàng.
 *  - Upload ảnh lên Cloudinary.
 *  - Lưu Item vào CSDL.
 *
 * Tương lai (khi mở gói Pro):
 *  - Gọi thêm RecipeService để đính kèm công thức.
 *  - Tạo ComboCreator riêng nếu loại COMBO có logic khác.
 */
class MenuItemCreator implements ItemCreatorInterface
{
    public function __construct(
        protected ItemRepositoryInterface $itemRepository,
        protected ItemGroupRepositoryInterface $itemGroupRepository
    ) {}

    public function create(string $restaurantId, array $data, ?UploadedFile $image = null): Item
    {
        // 1. Kiểm tra item_group_id phải thuộc về cùng restaurant_id
        if (isset($data['item_group_id'])) {
            $this->itemGroupRepository->findByIdAndRestaurantId($data['item_group_id'], $restaurantId);
        }

        $data['restaurant_id'] = $restaurantId;

        // 2. Upload ảnh lên Cloudinary (nếu có)
        if ($image) {
            $data['image_url'] = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                'folder' => "kiottay/{$restaurantId}/items",
            ])['secure_url'];
        }

        return $this->itemRepository->create($data);
    }
}
