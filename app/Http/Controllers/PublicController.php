<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompareArticle;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

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

        sort($slugs);
        $canonical = implode('-vs-', $slugs);
        if ($pair !== $canonical) {
            return redirect("/compare/{$canonical}", 301);
        }

        $matrix = [];
        foreach ($products as $p) {
            $latest = $p->latestSnapshot();
            $tiers = $latest ? $p->tiers()->where('snapshot_id', $latest->id)->get() : collect();
            $matrix[$p->slug] = ['product' => $p, 'tiers' => $tiers];
        }

        $article = CompareArticle::where('pair_slug', $canonical)->first();
        $articleHtml = $article ? Str::markdown($article->body_md) : null;

        return view('public.compare', compact('products', 'matrix', 'pair', 'article', 'articleHtml'));
    }

    public function compareMarkdown(string $pair)
    {
        $slugs = array_filter(explode('-vs-', $pair));
        sort($slugs);
        $canonical = implode('-vs-', $slugs);
        $article = CompareArticle::where('pair_slug', $canonical)->first();
        if (! $article) {
            abort(404);
        }

        $md = "---\n";
        $md .= "title: " . $article->title . "\n";
        $md .= "tldr: " . ($article->tldr ?? '') . "\n";
        $md .= "last_updated: " . $article->last_regenerated_at->toDateString() . "\n";
        $md .= "source: " . url("/compare/{$canonical}") . "\n";
        $md .= "---\n\n";
        $md .= $article->body_md;

        return Response::make($md, 200, ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public function sitemap()
    {
        $products = Product::where('is_active', true)->get();
        $articles = CompareArticle::all();

        $urls = [];
        $urls[] = ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => url('/llms.txt'), 'changefreq' => 'weekly', 'priority' => '0.5'];

        foreach ($products as $p) {
            $lastmod = $p->last_scraped_at?->toDateString();
            $urls[] = ['loc' => url("/tool/{$p->slug}"), 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.8'];
            $urls[] = ['loc' => url("/tool/{$p->slug}.md"), 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.7'];
        }
        foreach ($articles as $a) {
            $lastmod = $a->last_regenerated_at->toDateString();
            $urls[] = ['loc' => url("/compare/{$a->pair_slug}"), 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.9'];
            $urls[] = ['loc' => url("/compare/{$a->pair_slug}.md"), 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.8'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc']) . "</loc>\n";
            if (!empty($u['lastmod'])) $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            if (!empty($u['changefreq'])) $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            if (!empty($u['priority'])) $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
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
