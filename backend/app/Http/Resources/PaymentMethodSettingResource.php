<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RestaurantPaymentMethod
 */
class PaymentMethodSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'payment_method' => $this->payment_method->value,
            'method_label'   => $this->label(),
            'is_active'      => $this->is_active,
            'display_name'   => $this->display_name,
            'qr_code_url'    => $this->qrCodeUrl(), // null nếu chưa upload
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
