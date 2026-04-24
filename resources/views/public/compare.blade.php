@extends('public.layout')

@section('title', $products->pluck('name')->join(' vs ') . ' — PricePulse')
@section('description', 'Side-by-side pricing comparison of ' . $products->pluck('name')->join(', ') . '. Parsed from official vendor pricing pages.')

@section('content')
    <p class="muted"><a href="/">← all products</a></p>
    <h2 style="margin-top:8px;">{{ $products->pluck('name')->join(' vs ') }}</h2>

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
                $allTierNames = collect($matrix)
                    ->flatMap(fn ($r) => $r['tiers']->pluck('name'))
                    ->unique()
                    ->values();
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
