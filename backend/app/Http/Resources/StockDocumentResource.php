<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StockDocument
 */
class StockDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'restaurant_id'  => $this->restaurant_id,
            'warehouse_id'   => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn() => $this->warehouse->name),
            'document_type'  => $this->document_type->value,
            'document_label' => $this->document_type->label(),
            'code'           => $this->code,
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'note'           => $this->note,
            'created_by'     => $this->created_by,
            'creator_name'   => $this->whenLoaded('createdBy', fn() => $this->createdBy->name),
            'items'          => StockDocumentItemResource::collection(
                $this->whenLoaded('stockDocumentItems')
            ),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
