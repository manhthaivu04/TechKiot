<?php

namespace App\Contracts\Items;

use App\Models\Item;
use Illuminate\Http\UploadedFile;

/**
 * ItemCreatorInterface — Contract cho từng loại Creator.
 *
 * ── Factory Pattern ────────────────────────────────────────────────────────────
 * Mỗi loại item (MENU_ITEM, INGREDIENT, COMBO...) sẽ có một Creator riêng
 * implements interface này, giữ logic khởi tạo tách biệt và đúng trách nhiệm.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Thêm loại item mới? → Thêm 1 case vào ItemFactory + 1 class Creator mới.
 * Không sửa bất kỳ class nào khác.
 */
interface ItemCreatorInterface
{
    /**
     * Tạo mới một Item và lưu vào CSDL.
     *
     * @param  string            $restaurantId  UUID nhà hàng
     * @param  array             $data          Dữ liệu đã được validate từ FormRequest
     * @param  UploadedFile|null $image         File ảnh (nếu có)
     * @return Item
     */
    public function create(string $restaurantId, array $data, ?UploadedFile $image = null): Item;
}
