<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'warehouse_id'   => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'item_id'        => $this->item_id,
            'item_name'      => $this->item?->name,
            'item_unit'      => $this->item?->unit,
            'quantity'       => $this->quantity,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
