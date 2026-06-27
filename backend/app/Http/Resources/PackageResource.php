<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Package */
class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => $this->price,
            'duration_days' => $this->duration_days,
            'is_active'     => $this->is_active,
            'features'      => FeatureResource::collection($this->whenLoaded('features')),
            'prices'        => PackagePriceResource::collection($this->whenLoaded('prices')),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
