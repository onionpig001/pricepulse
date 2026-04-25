<?php

namespace App\Console\Commands;

use App\Models\CompareArticle;
use App\Models\Product;
use App\Services\ArticleGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateCompareArticle extends Command
{
    protected $signature = 'pricepulse:compare-article {pair? : e.g. notion-vs-linear}
                            {--all-pairs : regenerate every pair within the same category}
                            {--force : regenerate even if article already exists}';

    protected $description = 'Generate a Markdown comparison article for a product pair via Claude CLI';

    public function handle(): int
    {
        if ($this->option('all-pairs')) {
            return $this->runAllCategoryPairs();
        }

        $pair = $this->argument('pair');
        if (! $pair) {
            $this->error('Pass a pair like "notion-vs-linear" or use --all-pairs');
            return self::FAILURE;
        }

        return $this->runOne($pair) ? self::SUCCESS : self::FAILURE;
    }

    protected function runAllCategoryPairs(): int
    {
        $products = Product::where('is_active', true)
            ->whereNotNull('category_id')
            ->whereNotNull('last_scraped_at')
            ->get()
            ->groupBy('category_id');

        $pairs = [];
        foreach ($products as $group) {
            $arr = $group->values();
            for ($i = 0; $i < $arr->count(); $i++) {
                for ($j = $i + 1; $j < $arr->count(); $j++) {
                    $a = $arr[$i];
                    $b = $arr[$j];
                    $slug = CompareArticle::slugFor($a, $b);
                    $pairs[$slug] = [$a, $b];
                }
            }
        }

        $this->info('Planned pairs: ' . count($pairs));
        foreach ($pairs as $slug => [$a, $b]) {
            $this->runOne($slug);
        }
        return self::SUCCESS;
    }

    protected function runOne(string $pair): bool
    {
        $slugs = explode('-vs-', $pair);
        if (count($slugs) !== 2) {
            $this->error("Invalid pair: {$pair}");
            return false;
        }

        $a = Product::where('slug', $slugs[0])->first();
        $b = Product::where('slug', $slugs[1])->first();
        if (! $a || ! $b) {
            $this->error("Product(s) missing for pair {$pair}");
            return false;
        }

        $canonical = CompareArticle::slugFor($a, $b);
        if ($pair !== $canonical) {
            $this->line("→ normalized pair slug to {$canonical}");
        }

        $existing = CompareArticle::where('pair_slug', $canonical)->first();
        if ($existing && ! $this->option('force')) {
            $this->warn("  · {$canonical} exists, skip (use --force to regenerate)");
            return true;
        }

        $this->line("→ generating {$canonical}");

        try {
            $generator = ArticleGenerator::fromConfig();
            $parsed = $generator->compareArticle($a, $b);

            $orderedIds = [$a->id, $b->id];
            sort($orderedIds);

            CompareArticle::updateOrCreate(
                ['pair_slug' => $canonical],
                [
                    'product_a_id' => $orderedIds[0],
                    'product_b_id' => $orderedIds[1],
                    'title' => $parsed['title'],
                    'tldr' => $parsed['tldr'],
                    'body_md' => $parsed['body_md'],
                    'last_regenerated_at' => now(),
                ]
            );

            $words = str_word_count($parsed['body_md']);
            $this->info("  ✓ {$canonical}: {$words} words");
            return true;
        } catch (Throwable $e) {
            $this->error("  ✗ {$canonical}: {$e->getMessage()}");
            return false;
        }
    }
}
