<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class PricingExtractor
{
    public function __construct(
        private readonly string $claudeBin = '/root/.local/bin/claude',
        private readonly string $model = 'claude-sonnet-4-6',
        private readonly int $timeoutSeconds = 180,
    ) {}

    public static function fromConfig(): self
    {
        $bin = config('services.claude_cli.bin') ?: env('CLAUDE_CLI_BIN', '/root/.local/bin/claude');
        $model = config('services.claude_cli.model') ?: env('CLAUDE_CLI_MODEL', 'claude-sonnet-4-6');
        if (! is_executable($bin)) {
            throw new RuntimeException("Claude CLI not executable at {$bin}. Set CLAUDE_CLI_BIN in .env.");
        }
        return new self($bin, $model);
    }

    public function fetchHtml(string $url): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; PricePulseBot/0.1; +https://pricepulse.local)',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->get($url);

        return [
            'status' => $response->status(),
            'html' => $response->body(),
        ];
    }

    public function cleanHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#si', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#si', '', $html) ?? $html;
        $html = preg_replace('#<noscript\b[^>]*>.*?</noscript>#si', '', $html) ?? $html;
        $html = preg_replace('#<svg\b[^>]*>.*?</svg>#si', '', $html) ?? $html;
        $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;
        $html = preg_replace('#\s+#', ' ', $html) ?? $html;
        if (strlen($html) > 120000) {
            $html = substr($html, 0, 120000);
        }
        return trim($html);
    }

    public function extractTiers(string $productName, string $sourceUrl, string $cleanedHtml): array
    {
        $instructions = <<<SYS
You are a SaaS pricing page parser. Given the cleaned HTML of a vendor pricing page, extract every public paid tier plus the free tier (if shown).

Return a JSON object with this exact shape:
{
  "currency": "USD",
  "captured_url": "<the source URL>",
  "tiers": [
    {
      "name": "string",
      "price_monthly_usd": number|null,
      "price_annual_usd": number|null,
      "billing_unit": "user"|"seat"|"month"|null,
      "is_free": boolean,
      "is_custom_quote": boolean,
      "limits": { "free_text_key": "value" },
      "features": ["short bullet", ...]
    }
  ],
  "notes": "any caveats (e.g. annual-only pricing, regional variance)"
}

Rules:
- If a tier lists monthly and annual prices, capture both. If only one, set the other to null.
- Normalize everything to USD. If the page only shows EUR/GBP, convert using the posted rate if given, else keep the raw price and note it in "notes".
- Enterprise / "contact sales" tiers: is_custom_quote=true, prices=null.
- Free tier: is_free=true, prices=null unless the page quotes \$0.
- Respond with ONLY the JSON object, no prose, no markdown fences.
SYS;

        $prompt = $instructions . "\n\n---\n\nProduct: {$productName}\nSource URL: {$sourceUrl}\n\nCleaned HTML:\n{$cleanedHtml}";

        $process = new Process([
            $this->claudeBin,
            '-p',
            '--output-format', 'text',
            '--model', $this->model,
        ]);
        $process->setInput($prompt);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Claude CLI failed', [
                'exit' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new RuntimeException('Claude CLI exited ' . $process->getExitCode() . ': ' . $process->getErrorOutput());
        }

        $text = trim($process->getOutput());
        $text = preg_replace('/^```(?:json)?\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $json = json_decode($text, true);
        if (! is_array($json) || ! isset($json['tiers'])) {
            throw new RuntimeException("Model returned non-JSON (first 500 chars): " . substr($text, 0, 500));
        }

        return $json;
    }
}
