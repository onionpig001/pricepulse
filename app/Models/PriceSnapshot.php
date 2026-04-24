<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'parsed_tiers' => 'array',
        'diff_vs_previous' => 'array',
        'captured_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PricingTier::class, 'snapshot_id')->orderBy('tier_order');
    }
}
