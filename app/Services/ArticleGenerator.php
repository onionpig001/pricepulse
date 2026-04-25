<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class ArticleGenerator
{
    public function __construct(
        private readonly string $claudeBin,
        private readonly string $model,
        private readonly int $timeoutSeconds = 300,
    ) {}

    public static function fromConfig(): self
    {
        $bin = config('services.claude_cli.bin') ?: env('CLAUDE_CLI_BIN', '/root/.local/bin/claude');
        $model = config('services.claude_cli.model') ?: env('CLAUDE_CLI_MODEL', 'claude-sonnet-4-6');
        if (! is_executable($bin)) {
            throw new RuntimeException("Claude CLI not executable at {$bin}");
        }
        return new self($bin, $model);
    }

    public function compareArticle(Product $a, Product $b): array
    {
        $dataA = $this->productBrief($a);
        $dataB = $this->productBrief($b);

        $instructions = <<<SYS
You write comparison articles for PricePulse, a SaaS pricing tracker. You are given STRUCTURED pricing data (already parsed from vendor pages) for two products. Write an honest, opinionated, AI-citable comparison article in Markdown.

Constraints:
- 900–1400 words.
- Start with a YAML frontmatter block containing ONLY these keys: title, tldr. `tldr` is a single-line string (not a list) summarizing the core tradeoff in one sentence.
- Then the Markdown body. Do NOT repeat the title as an H1.
- Required sections, in this order:
  1. `## TL;DR` — 4–6 bullet points, each starting with which product wins on that axis (or "tie").
  2. `## Pricing at a glance` — a markdown table comparing tier names + monthly prices.
  3. `## Who should pick {A}` — 2–4 concrete scenarios.
  4. `## Who should pick {B}` — 2–4 concrete scenarios.
  5. `## Gotchas & edge cases` — 3–6 bullets of surprising facts (hidden seat minimums, feature locks, annual-only pricing, migration pain).
  6. `## Bottom line` — one paragraph with a clear recommendation.
- Use numbers from the data verbatim (don't invent prices).
- Cite each product's homepage with a plain markdown link the first time you name it.
- No hype. No "in today's fast-paced world". No em dashes.
- Return ONLY the article (YAML frontmatter + body). No prose before or after. No ```markdown fences.
SYS;

        $payload = "Product A:\n" . json_encode($dataA, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        $payload .= "Product B:\n" . json_encode($dataB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        $prompt = $instructions . "\n\n---\n\n" . $payload;

        $process = new Process([
            $this->claudeBin, '-p', '--output-format', 'text', '--model', $this->model,
        ]);
        $process->setInput($prompt);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Claude CLI failed for compare article', [
                'exit' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new RuntimeException('Claude CLI exited ' . $process->getExitCode());
        }

        $text = trim($process->getOutput());
        $text = preg_replace('/^```\w*\s*\n?/', '', $text);
        $text = preg_replace('/\n?```\s*$/', '', $text);
        $text = trim($text);

        return $this->parseFrontmatter($text);
    }

    private function productBrief(Product $p): array
    {
        $latest = $p->latestSnapshot();
        $tiers = $latest ? $p->tiers()->where('snapshot_id', $latest->id)->get() : collect();

        return [
            'name' => $p->name,
            'slug' => $p->slug,
            'vendor' => $p->vendor,
            'homepage_url' => $p->homepage_url,
            'pricing_url' => $p->pricing_url,
            'tagline' => $p->tagline,
            'category' => $p->category?->name,
            'last_checked' => $p->last_scraped_at?->toDateString(),
            'tiers' => $tiers->map(fn ($t) => [
                'name' => $t->name,
                'monthly_usd' => $t->is_free ? 0 : ($t->is_custom_quote ? null : $t->price_monthly_usd),
                'annual_usd' => $t->is_free ? 0 : ($t->is_custom_quote ? null : $t->price_annual_usd),
                'billing_unit' => $t->billing_unit,
                'is_free' => $t->is_free,
                'is_custom_quote' => $t->is_custom_quote,
                'features' => $t->features,
            ])->values()->all(),
            'notes' => $latest?->parsed_tiers['notes'] ?? null,
        ];
    }

    private function parseFrontmatter(string $text): array
    {
        $title = null;
        $tldr = null;
        $body = $text;

        if (preg_match('/(?:^|\A)(?:```\w*\s*\n)?---\s*\n(.*?)\n---\s*\n(?:```\s*\n)?(.*)$/s', $text, $m)) {
            $yaml = $m[1];
            $body = trim($m[2]);
            if (preg_match('/^title:\s*(.+)$/m', $yaml, $tm)) {
                $title = trim($tm[1], " \t\"'");
            }
            if (preg_match('/^tldr:\s*(.+)$/m', $yaml, $dm)) {
                $tldr = trim($dm[1], " \t\"'");
            }
        }

        if (! $title) {
            throw new RuntimeException('Generated article missing YAML title. First 500 chars: ' . substr($text, 0, 500));
        }

        return ['title' => $title, 'tldr' => $tldr, 'body_md' => $body];
    }
}
