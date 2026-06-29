<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePrice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'package_id',
        'duration_days',
        'price',
        'original_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_days'  => 'integer',
            'price'          => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
