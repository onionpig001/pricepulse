@extends('public.layout')

@section('title', 'PricePulse — Live SaaS Pricing, Compared')
@section('description', 'Tracked, structured, machine-readable pricing for the SaaS tools teams actually use. Updated weekly.')

@section('content')
    <p class="muted" style="font-size:15px; max-width:620px;">
        PricePulse is a living index of SaaS pricing pages. Every product has an HTML page for humans and a Markdown twin at <code>/tool/&lt;slug&gt;.md</code> for AI crawlers. Prices are parsed directly from vendor pages — no middlemen, no stale blog posts.
    </p>

    @if ($recentChanges->isNotEmpty())
        <h2>Recent price changes</h2>
        @foreach ($recentChanges as $snap)
            @php $diffs = $snap->diff_vs_previous ?? []; @endphp
            @if (!empty($diffs))
                <div class="card">
                    <strong><a href="{{ route('tool', $snap->product->slug) }}">{{ $snap->product->name }}</a></strong>
                    <span class="muted">— {{ $snap->captured_at->toDateString() }}</span>
                    <ul class="features">
                        @foreach ($diffs as $d)
                            <li>
                                @if ($d['type'] === 'added')
                                    <span class="badge">added</span> {{ $d['tier'] }}
                                @elseif ($d['type'] === 'removed')
                                    <span class="badge">removed</span> {{ $d['tier'] }}
                                @else
                                    <span class="badge change">changed</span>
                                    <strong>{{ $d['tier'] }}</strong> · {{ $d['field'] ?? '' }}: {{ $d['from'] ?? '—' }} → <strong>{{ $d['to'] ?? '—' }}</strong>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    @endif

    <h2>All tracked products</h2>
    <div class="grid">
        @foreach ($products as $p)
            <div class="card">
                <strong><a href="{{ route('tool', $p->slug) }}">{{ $p->name }}</a></strong>
                @if ($p->category)
                    <span class="muted"> · {{ $p->category->name }}</span>
                @endif
                <p class="muted" style="margin:6px 0 4px;">{{ $p->tagline }}</p>
                <p class="muted" style="font-size:12px;">
                    @if ($p->last_scraped_at)
                        updated {{ $p->last_scraped_at->diffForHumans() }}
                    @else
                        not yet scraped
                    @endif
                </p>
            </div>
        @endforeach
    </div>
@endsection
