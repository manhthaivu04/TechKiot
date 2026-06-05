<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => (float) $this->amount,
            'payment_method' => $this->payment_method->value,
            'method_label'   => $this->payment_method->label(),
            'reference_no'   => $this->reference_no,
            'paid_at'        => $this->paid_at?->toIso8601String(),
        ];
    }
}
