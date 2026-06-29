<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PackagePrice */
class PackagePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'package_id'     => $this->package_id,
            'duration_days'  => $this->duration_days,
            'price'          => $this->price,
            'original_price' => $this->original_price,
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
