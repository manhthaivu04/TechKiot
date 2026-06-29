<?php

namespace App\Services;

use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Contracts\Repositories\ItemGroupRepositoryInterface;
use App\Contracts\Services\ItemServiceInterface;
use App\Enums\ItemType;
use App\Events\IngredientCostPriceUpdated;
use App\Services\Items\ItemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class ItemService implements ItemServiceInterface
{
    public function __construct(
        protected ItemRepositoryInterface $itemRepository,
        protected ItemGroupRepositoryInterface $itemGroupRepository
    ) {}

    public function getAllItems(string $restaurantId, array $filters = [])
    {
        return $this->itemRepository->getByRestaurantId($restaurantId, $filters);
    }

    public function getItemById(string $id, string $restaurantId)
    {
        return $this->itemRepository->findByIdAndRestaurantId($id, $restaurantId);
    }

    public function createItem(string $restaurantId, array $data, ?UploadedFile $image = null)
    {
        // Uỷ quyền việc tạo cho Creator tương ứng với item_type.
        // Thêm loại item mới → chỉ cần thêm case vào ItemFactory, không sửa Service này.
        $creator = ItemFactory::make($data['item_type']);

        return $creator->create($restaurantId, $data, $image);
    }

    public function updateItem(string $id, string $restaurantId, array $data, ?UploadedFile $image = null)
    {
        $item = $this->getItemById($id, $restaurantId);

        if (isset($data['item_group_id'])) {
            $this->itemGroupRepository->findByIdAndRestaurantId($data['item_group_id'], $restaurantId);
        }

        if ($image) {
            // Xóa ảnh cũ trên Cloudinary (nếu có)
            if ($item->image_url) {
                $this->deleteCloudinaryImage($item->image_url);
            }

            // Upload ảnh mới
            $uploadedFileUrl = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                'folder' => "kiottay/{$restaurantId}/items"
            ])['secure_url'];
            
            $data['image_url'] = $uploadedFileUrl;
        }

        $oldCostPrice = $item->item_type === ItemType::INGREDIENT
            ? (float) $item->cost_price
            : null;

        $updatedItem = $this->itemRepository->update($item, $data);

        if ($updatedItem->item_type === ItemType::INGREDIENT
            && array_key_exists('cost_price', $data)
            && round((float) $updatedItem->cost_price, 2) !== round($oldCostPrice, 2)
        ) {
            IngredientCostPriceUpdated::dispatch($updatedItem);
        }

        return $updatedItem;
    }

    public function deleteItem(string $id, string $restaurantId)
    {
        $item = $this->getItemById($id, $restaurantId);

        DB::beginTransaction();
        try {
            // Xóa ảnh trên Cloudinary trước
            if ($item->image_url) {
                $this->deleteCloudinaryImage($item->image_url);
            }

            $result = $this->itemRepository->delete($item);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Helper method để extract public_id từ URL Cloudinary và xóa
     */
    protected function deleteCloudinaryImage(string $imageUrl): void
    {
        try {
            // Ví dụ URL: https://res.cloudinary.com/demo/image/upload/v1234567890/kiottay/rest_id/items/abcxyz.jpg
            // public_id sẽ là: kiottay/rest_id/items/abcxyz
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            
            // Tìm vị trí của folder 'kiottay' để cắt chuỗi
            $startPos = strpos($pathInfo['dirname'], 'kiottay');
            if ($startPos !== false) {
                $folderPath = substr($pathInfo['dirname'], $startPos);
                $publicId = $folderPath . '/' . $pathInfo['filename'];
                
                cloudinary()->uploadApi()->destroy($publicId);
            }
        } catch (Exception $e) {
            // Log error nhưng không throw để tránh block việc xóa bản ghi DB
            \Log::error("Failed to delete Cloudinary image: {$imageUrl}. Error: " . $e->getMessage());
        }
    }
}
