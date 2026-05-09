<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Restaurant */
class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'address'             => $this->address,
            'phone'               => $this->phone,
            'public_order_token'  => $this->public_order_token,
            'status'              => $this->status?->value ?? 'active',
            'status_label'        => $this->status?->label() ?? 'Hoạt động',
            'active_subscription' => new SubscriptionResource($this->whenLoaded('activeSubscription')),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
