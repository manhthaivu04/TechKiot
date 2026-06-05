<?php

namespace App\Services\Menu;

use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Database\Eloquent\Collection;

/**
 * MenuGrouper — Thuật toán gom nhóm Items theo Danh mục (ItemGroup).
 *
 * ── Tại sao tách ra class riêng? ────────────────────────────────────────────
 * Giống như OrderService dùng OrderStrategyResolver để tách logic resolve,
 * các Strategy (QrStaticMenuStrategy, QrTableMenuStrategy…) đều cần
 * cùng thuật toán gom nhóm. Tách ra đây để:
 *
 * 1. SRP: Strategy chỉ lo resolve restaurant_id và fetch data.
 *    Thuật toán gom nhóm nằm ở đây, không bị lặp trong từng Strategy.
 *
 * 2. DRY: Khi thêm QrTableMenuStrategy, chỉ inject MenuGrouper vào,
 *    không viết lại vòng lặp.
 *
 * 3. Testability: Unit test thuật toán gom nhóm độc lập, không cần DB.
 *
 * Output structure:
 * [
 *   {
 *     "group_id":      "uuid",
 *     "group_name":    "Món chính",
 *     "display_order": 1,
 *     "items": [
 *       { "id": "...", "name": "Phở bò", "sale_price": "55000.00", ... }
 *     ]
 *   },
 *   ...
 * ]
 */
class MenuGrouper
{
    /**
     * Gom danh sách Items (đã eager-load itemGroup) thành cấu trúc
     * phân cấp: nhóm → items bên trong nhóm đó.
     *
     * @param  Collection<int, Item> $items Đã eager-load quan hệ itemGroup
     * @return array<int, mixed>
     */
    public function group(Collection $items): array
    {
        // Tách items có group và không có group
        [$withGroup, $withoutGroup] = $items->partition(
            fn (Item $item): bool => $item->itemGroup !== null
        );

        $result = [];

        // ── Nhóm items theo group, sort theo display_order ───────────────────
        $grouped = $withGroup->groupBy('item_group_id');

        $sortedGroups = $withGroup
            ->map(fn (Item $item): ItemGroup => $item->itemGroup)
            ->unique('id')
            ->sortBy('display_order')
            ->values();

        foreach ($sortedGroups as $group) {
            $result[] = [
                'group_id'      => $group->id,
                'group_name'    => $group->name,
                'display_order' => $group->display_order,
                'items'         => $grouped->get($group->id, collect())
                                           ->map(fn (Item $item) => $this->formatItem($item))
                                           ->values()
                                           ->all(),
            ];
        }

        // ── Items không thuộc nhóm nào → gom cuối danh sách ────────────────
        if ($withoutGroup->isNotEmpty()) {
            $result[] = [
                'group_id'      => null,
                'group_name'    => 'Khác',
                'display_order' => PHP_INT_MAX,
                'items'         => $withoutGroup->map(fn (Item $item) => $this->formatItem($item))
                                                ->values()
                                                ->all(),
            ];
        }

        return $result;
    }

    /**
     * Map một Item Eloquent model thành mảng trả về cho client.
     *
     * @return array<string, mixed>
     */
    private function formatItem(Item $item): array
    {
        return [
            'id'                  => $item->id,
            'name'                => $item->name,
            'item_type'           => $item->item_type->value,
            'unit'                => $item->unit,
            'image_url'           => $item->image_url,
            'description'         => $item->description,
            'sale_price'          => $item->sale_price,
            'availability_status' => $item->availability_status->value,
        ];
    }
}
