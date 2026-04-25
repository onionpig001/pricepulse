@extends('public.layout')

@section('title', $article?->title ?? ($products->pluck('name')->join(' vs ') . ' — PricePulse'))
@section('description', $article?->tldr ?? 'Side-by-side pricing comparison of ' . $products->pluck('name')->join(', ') . '. Parsed from official vendor pricing pages.')

@push('head')
    @php
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => $article ? 'Article' : 'WebPage',
            'headline' => $article?->title ?? ($products->pluck('name')->join(' vs ')),
            'datePublished' => $article?->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'dateModified' => $article?->last_regenerated_at?->toIso8601String() ?? now()->toIso8601String(),
            'description' => $article?->tldr ?? '',
            'about' => $products->map(fn ($p) => [
                '@type' => 'SoftwareApplication',
                'name' => $p->name,
                'url' => $p->homepage_url,
            ])->values()->all(),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @if ($article)
        <link rel="alternate" type="text/markdown" href="/compare/{{ $pair }}.md" title="Markdown version">
    @endif
@endpush

@section('content')
    <p class="muted"><a href="/">← all products</a></p>

    @if ($article)
        <h2 style="margin-top:8px;">{{ $article->title }}</h2>
        @if ($article->tldr)
            <p class="muted" style="font-size:15px; max-width:720px;">{{ $article->tldr }}</p>
        @endif
        <p class="muted" style="font-size:12px;">
            Last updated {{ $article->last_regenerated_at->toDateString() }}
            · <a href="/compare/{{ $pair }}.md">.md</a>
        </p>

        <div class="article">
            {!! $articleHtml !!}
        </div>
    @else
        <h2 style="margin-top:8px;">{{ $products->pluck('name')->join(' vs ') }}</h2>
        <p class="muted">No generated article yet. Run <code>php artisan pricepulse:compare-article {{ $pair }}</code>.</p>
    @endif

    <h2>Raw matrix</h2>
    <table>
        <thead>
            <tr>
                <th>Tier</th>
                @foreach ($matrix as $row)
                    <th>{{ $row['product']->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $allTierNames = collect($matrix)->flatMap(fn ($r) => $r['tiers']->pluck('name'))->unique()->values();
            @endphp
            @foreach ($allTierNames as $tierName)
                <tr>
                    <td><strong>{{ $tierName }}</strong></td>
                    @foreach ($matrix as $row)
                        @php $t = $row['tiers']->firstWhere('name', $tierName); @endphp
                        <td>
                            @if ($t)
                                @if ($t->is_free) Free
                                @elseif ($t->is_custom_quote) Custom
                                @elseif ($t->price_monthly_usd !== null)
                                    ${{ rtrim(rtrim(number_format((float)$t->price_monthly_usd, 2), '0'), '.') }}/mo{{ $t->billing_unit ? '/'.$t->billing_unit : '' }}
                                @elseif ($t->price_annual_usd !== null)
                                    ${{ rtrim(rtrim(number_format((float)$t->price_annual_usd, 2), '0'), '.') }}/yr
                                @else —
                                @endif
                            @else
                                <span class="muted">n/a</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="font-size:13px;">
        Sources:
        @foreach ($products as $p)
            <a href="{{ $p->pricing_url }}" rel="nofollow">{{ $p->pricing_url }}</a>@if (!$loop->last), @endif
        @endforeach
    </p>
@endsection
