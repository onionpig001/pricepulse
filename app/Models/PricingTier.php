<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingTier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'limits' => 'array',
        'features' => 'array',
        'is_free' => 'boolean',
        'is_custom_quote' => 'boolean',
        'captured_at' => 'datetime',
        'price_monthly_usd' => 'decimal:2',
        'price_annual_usd' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(PriceSnapshot::class, 'snapshot_id');
    }

    public function formattedMonthlyPrice(): string
    {
        if ($this->is_free) return 'Free';
        if ($this->is_custom_quote) return 'Custom';
        if ($this->price_monthly_usd === null) return '—';
        $unit = $this->billing_unit ? " /{$this->billing_unit}" : '';
        return '$' . number_format((float) $this->price_monthly_usd, 2) . '/mo' . $unit;
    }
}
