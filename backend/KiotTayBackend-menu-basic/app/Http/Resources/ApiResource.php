<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent API resource wrapper.
 * Wraps any JsonResource/Collection with a standard envelope.
 */
class ApiResource extends JsonResource
{
    public static function make(mixed $resource): static
    {
        return parent::make($resource);
    }
}
