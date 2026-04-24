<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class)->orderByDesc('captured_at');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PricingTier::class)->orderBy('tier_order');
    }

    public function latestSnapshot(): ?PriceSnapshot
    {
        return $this->snapshots()->first();
    }

    public function currentTiers()
    {
        $latest = $this->latestSnapshot();
        if (! $latest) {
            return collect();
        }
        return $this->tiers()->where('snapshot_id', $latest->id)->get();
    }
}
