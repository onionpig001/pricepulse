<?php

namespace App\Console\Commands;

use App\Models\PriceSnapshot;
use App\Models\PricingTier;
use App\Models\Product;
use App\Services\PricingExtractor;
use Illuminate\Console\Command;
use Throwable;

class ScrapeProduct extends Command
{
    protected $signature = 'pricepulse:scrape {slug? : product slug}
                            {--all : scrape every active product}
                            {--skip-llm : store raw HTML only, no LLM parse}';

    protected $description = 'Fetch a product pricing page, extract tiers via Claude, store snapshot';

    public function handle(): int
    {
        $query = Product::query()->where('is_active', true);

        if ($this->option('all')) {
            $products = $query->get();
        } elseif ($slug = $this->argument('slug')) {
            $products = $query->where('slug', $slug)->get();
            if ($products->isEmpty()) {
                $this->error("No active product with slug={$slug}");
                return self::FAILURE;
            }
        } else {
            $this->error('Pass a slug or --all');
            return self::FAILURE;
        }

        foreach ($products as $i => $product) {
            $this->runOne($product);
            if ($products->count() > 1 && $i < $products->count() - 1) {
                sleep(8);
            }
        }

        return self::SUCCESS;
    }

    protected function runOne(Product $product): void
    {
        $this->line("→ scraping {$product->slug} :: {$product->pricing_url}");

        try {
            $extractor = PricingExtractor::fromConfig();
            $fetch = $extractor->fetchHtml($product->pricing_url);
            $cleaned = $extractor->cleanHtml($fetch['html']);

            $parsed = ['tiers' => [], 'notes' => 'LLM parse skipped'];
            if (! $this->option('skip-llm')) {
                $parsed = $extractor->extractTiers($product->name, $product->pricing_url, $cleaned);
            }

            $prev = $product->latestSnapshot();
            $diff = $this->computeDiff($prev?->parsed_tiers ?? [], $parsed['tiers'] ?? []);

            $snapshot = PriceSnapshot::create([
                'product_id' => $product->id,
                'html_raw' => $fetch['html'],
                'parsed_tiers' => $parsed,
                'diff_vs_previous' => $diff,
                'source_url' => $product->pricing_url,
                'http_status' => (string) $fetch['status'],
                'captured_at' => now(),
            ]);

            foreach ($parsed['tiers'] ?? [] as $i => $tier) {
                PricingTier::create([
                    'product_id' => $product->id,
                    'snapshot_id' => $snapshot->id,
                    'name' => $tier['name'] ?? "Tier {$i}",
                    'price_monthly_usd' => $tier['price_monthly_usd'] ?? null,
                    'price_annual_usd' => $tier['price_annual_usd'] ?? null,
                    'billing_unit' => $tier['billing_unit'] ?? null,
                    'is_free' => (bool) ($tier['is_free'] ?? false),
                    'is_custom_quote' => (bool) ($tier['is_custom_quote'] ?? false),
                    'limits' => $tier['limits'] ?? null,
                    'features' => $tier['features'] ?? null,
                    'tier_order' => $i,
                    'captured_at' => now(),
                ]);
            }

            $product->update(['last_scraped_at' => now()]);
            $this->info("  ✓ {$product->slug}: " . count($parsed['tiers'] ?? []) . ' tiers, ' . count($diff) . ' diffs');
        } catch (Throwable $e) {
            $this->error("  ✗ {$product->slug}: {$e->getMessage()}");
        }
    }

    protected function computeDiff(array $prevParsed, array $newTiers): array
    {
        $prevTiers = $prevParsed['tiers'] ?? $prevParsed;
        $prev = collect(is_array($prevTiers) ? $prevTiers : [])->keyBy('name');
        $new = collect($newTiers)->keyBy('name');
        $changes = [];

        foreach ($new as $name => $tier) {
            $before = $prev[$name] ?? null;
            if (! $before) {
                $changes[] = ['tier' => $name, 'type' => 'added'];
                continue;
            }
            foreach (['price_monthly_usd', 'price_annual_usd', 'is_custom_quote'] as $field) {
                if (($before[$field] ?? null) !== ($tier[$field] ?? null)) {
                    $changes[] = [
                        'tier' => $name,
                        'type' => 'changed',
                        'field' => $field,
                        'from' => $before[$field] ?? null,
                        'to' => $tier[$field] ?? null,
                    ];
                }
            }
        }
        foreach ($prev as $name => $_) {
            if (! isset($new[$name])) {
                $changes[] = ['tier' => $name, 'type' => 'removed'];
            }
        }

        return $changes;
    }
}
