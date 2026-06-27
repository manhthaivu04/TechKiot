<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StockDocumentItem
 */
class StockDocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'item_id'    => $this->item_id,
            'item_name'  => $this->whenLoaded('item', fn() => $this->item->name),
            'item_unit'  => $this->whenLoaded('item', fn() => $this->item->unit),
            'quantity'   => $this->quantity,
            'unit_cost'  => $this->unit_cost,
            'total_cost' => $this->total_cost,
        ];
    }
}
