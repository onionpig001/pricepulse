<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowNotifier
{
    public function __construct(
        private readonly ?string $key,
        private readonly string $host,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('services.indexnow.key'),
            config('services.indexnow.host', 'pricepulse.onionpig.com'),
        );
    }

    public function notify(array $urls): bool
    {
        if (! $this->key || empty($urls)) {
            return false;
        }

        $urls = array_values(array_unique($urls));

        try {
            $response = Http::timeout(15)->post('https://api.indexnow.org/indexnow', [
                'host' => $this->host,
                'key' => $this->key,
                'keyLocation' => "https://{$this->host}/{$this->key}.txt",
                'urlList' => $urls,
            ]);

            if (! $response->successful()) {
                Log::warning('IndexNow ping non-2xx', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning('IndexNow ping failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
