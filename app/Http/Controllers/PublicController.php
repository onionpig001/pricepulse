<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PublicController extends Controller
{
    public function home()
    {
        $products = Product::where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();

        $recentChanges = PriceSnapshot::query()
            ->with('product')
            ->whereNotNull('diff_vs_previous')
            ->whereRaw("json_array_length(COALESCE(diff_vs_previous, '[]')) > 0")
            ->orderByDesc('captured_at')
            ->limit(15)
            ->get();

        $categories = Category::with(['products' => fn ($q) => $q->where('is_active', true)])->get();

        return view('public.home', compact('products', 'recentChanges', 'categories'));
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();

        $latest = $product->latestSnapshot();
        $currentTiers = $latest ? $product->tiers()->where('snapshot_id', $latest->id)->get() : collect();
        $history = $product->snapshots()->limit(30)->get();

        return view('public.tool', compact('product', 'latest', 'currentTiers', 'history'));
    }

    public function showMarkdown(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $latest = $product->latestSnapshot();
        $tiers = $latest ? $product->tiers()->where('snapshot_id', $latest->id)->get() : collect();

        $md = "# {$product->name} Pricing\n\n";
        $md .= "> {$product->tagline}\n\n";
        $md .= "**Source:** {$product->pricing_url}\n";
        $md .= '**Last checked:** ' . ($product->last_scraped_at?->toDateString() ?? 'never') . "\n\n";

        if ($tiers->isEmpty()) {
            $md .= "_No pricing data captured yet._\n";
        } else {
            $md .= "## Current Plans\n\n";
            $md .= "| Tier | Monthly (USD) | Annual (USD) | Billing | Notes |\n";
            $md .= "|---|---|---|---|---|\n";
            foreach ($tiers as $t) {
                $monthly = $t->is_free ? 'Free' : ($t->is_custom_quote ? 'Custom' : ($t->price_monthly_usd !== null ? '$' . $t->price_monthly_usd : '—'));
                $annual = $t->is_free ? 'Free' : ($t->is_custom_quote ? 'Custom' : ($t->price_annual_usd !== null ? '$' . $t->price_annual_usd : '—'));
                $unit = $t->billing_unit ?: '—';
                $notes = '';
                $md .= "| {$t->name} | {$monthly} | {$annual} | {$unit} | {$notes} |\n";
            }
            $md .= "\n";
            foreach ($tiers as $t) {
                if (! $t->features) continue;
                $md .= "### {$t->name} features\n";
                foreach ($t->features as $f) {
                    $md .= "- {$f}\n";
                }
                $md .= "\n";
            }
        }

        return Response::make($md, 200, ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public function compare(string $pair)
    {
        $slugs = array_filter(explode('-vs-', $pair));
        if (count($slugs) < 2) {
            abort(404);
        }
        $products = Product::whereIn('slug', $slugs)->where('is_active', true)->get();
        if ($products->count() < 2) {
            abort(404);
        }

        $matrix = [];
        foreach ($products as $p) {
            $latest = $p->latestSnapshot();
            $tiers = $latest ? $p->tiers()->where('snapshot_id', $latest->id)->get() : collect();
            $matrix[$p->slug] = ['product' => $p, 'tiers' => $tiers];
        }

        return view('public.compare', compact('products', 'matrix', 'pair'));
    }

    public function llmsTxt()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $body = "# PricePulse\n";
        $body .= "> Up-to-date SaaS pricing. Every page has a .md twin for machine ingestion.\n\n";
        $body .= "## Tracked products\n\n";
        foreach ($products as $p) {
            $url = url("/tool/{$p->slug}");
            $body .= "- [{$p->name}]({$url}.md): {$p->tagline}\n";
        }
        $body .= "\n## Endpoints\n\n";
        $body .= "- `/` — homepage with recent price changes\n";
        $body .= "- `/tool/{slug}` — HTML pricing page\n";
        $body .= "- `/tool/{slug}.md` — Markdown twin (structured, for AI)\n";
        $body .= "- `/compare/{slug-a}-vs-{slug-b}` — pairwise comparison\n";

        return Response::make($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
