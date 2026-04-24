@extends('public.layout')

@section('title', $product->name . ' Pricing — PricePulse')
@section('description', 'Live ' . $product->name . ' pricing tiers, parsed from the official pricing page. ' . ($product->tagline ?? ''))

@push('head')
    @php
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $product->name,
            'applicationCategory' => $product->category?->name ?? 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $product->homepage_url,
            'description' => $product->tagline ?? $product->description ?? '',
            'offers' => $currentTiers->map(function ($t) {
                return [
                    '@type' => 'Offer',
                    'name' => $t->name,
                    'price' => $t->is_free ? '0' : (string) ($t->price_monthly_usd ?? ''),
                    'priceCurrency' => 'USD',
                    'priceSpecification' => [
                        '@type' => 'UnitPriceSpecification',
                        'price' => $t->is_free ? '0' : (string) ($t->price_monthly_usd ?? ''),
                        'priceCurrency' => 'USD',
                        'referenceQuantity' => [
                            '@type' => 'QuantitativeValue',
                            'unitCode' => 'MON',
                            'unitText' => $t->billing_unit ?? 'month',
                        ],
                    ],
                ];
            })->values()->all(),
            'dateModified' => $product->last_scraped_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <link rel="alternate" type="text/markdown" href="/tool/{{ $product->slug }}.md" title="Markdown version">
@endpush

@section('content')
    <p class="muted"><a href="/">← all products</a></p>
    <h2 style="margin-top:8px;">{{ $product->name }}</h2>
    <p class="muted">{{ $product->tagline }}</p>
    <p class="muted" style="font-size:13px;">
        Source: <a href="{{ $product->pricing_url }}" rel="nofollow">{{ $product->pricing_url }}</a>
        @if ($product->last_scraped_at)
            · last checked {{ $product->last_scraped_at->toDateString() }}
        @endif
        · <a href="/tool/{{ $product->slug }}.md">.md</a>
    </p>

    @if ($currentTiers->isEmpty())
        <div class="card">
            <p class="muted">No pricing snapshot yet. Run <code>php artisan pricepulse:scrape {{ $product->slug }}</code>.</p>
        </div>
    @else
        <h3>Current plans</h3>
        <div class="grid">
            @foreach ($currentTiers as $t)
                <div class="card">
                    <div class="muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.04em;">{{ $t->name }}</div>
                    <div class="tier-price" style="margin-top:6px;">
                        @if ($t->is_free)
                            <span class="badge free">Free</span>
                        @elseif ($t->is_custom_quote)
                            Custom
                        @elseif ($t->price_monthly_usd !== null)
                            ${{ rtrim(rtrim(number_format((float)$t->price_monthly_usd, 2), '0'), '.') }}<span class="unit">/mo{{ $t->billing_unit ? '/'.$t->billing_unit : '' }}</span>
                        @else
                            —
                        @endif
                    </div>
                    @if ($t->price_annual_usd !== null && !$t->is_free)
                        <div class="muted" style="font-size:12px; margin-top:4px;">
                            ${{ rtrim(rtrim(number_format((float)$t->price_annual_usd, 2), '0'), '.') }} annual
                        </div>
                    @endif
                    @if (!empty($t->features))
                        <ul class="features">
                            @foreach (array_slice($t->features, 0, 6) as $f)
                                <li>{{ $f }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($history->count() > 1)
        <h3>History</h3>
        <table>
            <thead><tr><th>Captured</th><th>Tiers</th><th>Changes</th></tr></thead>
            <tbody>
            @foreach ($history as $s)
                <tr>
                    <td>{{ $s->captured_at->toDateString() }}</td>
                    <td>{{ count($s->parsed_tiers['tiers'] ?? []) }}</td>
                    <td>{{ count($s->diff_vs_previous ?? []) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if ($product->affiliate_url)
        <a class="cta" href="{{ $product->affiliate_url }}" rel="sponsored nofollow">Try {{ $product->name }} →</a>
    @elseif ($product->homepage_url)
        <a class="cta" href="{{ $product->homepage_url }}" rel="nofollow">Visit {{ $product->name }} →</a>
    @endif
@endsection
