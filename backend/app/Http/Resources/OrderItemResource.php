<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'item_id'        => $this->item_id,
            'item_name'      => $this->whenLoaded('item', fn() => $this->item->name),
            'item_image'     => $this->whenLoaded('item', fn() => $this->item->image_url),
            'quantity'   => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) $this->line_total,
            'status'     => $this->status->value,
            'note'       => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
