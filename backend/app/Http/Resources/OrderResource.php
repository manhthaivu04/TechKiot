<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'source_channel' => $this->source_channel->value,
            'service_type' => $this->service_type,
            'status' => $this->status->value,
            'status_label' => $this->state()->label(),
            'table_id' => $this->table_id,

            // Khách hàng
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_reference' => $this->customer_reference,
            'guest_count' => $this->guest_count,

            // Tài chính
            'subtotal_amount' => (float) $this->subtotal_amount,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'final_amount' => (float) $this->final_amount,

            'note' => $this->note,

            // Relationships (load khi có)
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
