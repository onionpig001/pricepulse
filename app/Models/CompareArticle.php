<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompareArticle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_regenerated_at' => 'datetime',
    ];

    public function productA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    public function productB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }

    public static function slugFor(Product $a, Product $b): string
    {
        $slugs = [$a->slug, $b->slug];
        sort($slugs);
        return implode('-vs-', $slugs);
    }
}
