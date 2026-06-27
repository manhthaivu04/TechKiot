<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TransactionType;

class InventoryTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'warehouse_id'      => $this->warehouse_id,
            'warehouse_name'    => $this->warehouse?->name,
            'item_id'           => $this->item_id,
            'item_name'         => $this->item?->name,
            'item_unit'         => $this->item?->unit,
            'transaction_type'  => $this->transaction_type->value,
            'transaction_label' => $this->getTransactionLabel($this->transaction_type),
            'reference_type'    => $this->reference_type,
            'reference_id'      => $this->reference_id,
            'quantity_change'   => $this->quantity_change,
            'before_quantity'   => $this->before_quantity,
            'after_quantity'    => $this->after_quantity,
            'note'              => $this->note,
            'created_by'        => $this->created_by,
            'creator_name'      => $this->createdBy?->name,
            'created_at'        => $this->created_at,
        ];
    }

    private function getTransactionLabel(TransactionType $type): string
    {
        return match ($type) {
            TransactionType::RECEIPT    => 'Nhập kho',
            TransactionType::ISSUE      => 'Xuất kho',
            TransactionType::ADJUSTMENT => 'Điều chỉnh',
            TransactionType::WASTE      => 'Hủy hàng',
            TransactionType::RETURN     => 'Trả hàng',
            TransactionType::RECIPE_USE => 'Bán hàng (BOM)',
        };
    }
}
