<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->reference }} — Midland Catering</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-app p-6 text-text">
    @php $mc = config('midland'); @endphp

    <div class="mx-auto max-w-3xl">
        <div class="no-print mb-4 flex justify-end gap-2">
            <button type="button" onclick="window.print()"
                    class="tap rounded-lg bg-gold px-4 text-sm font-semibold text-ink">{{ __('Print') }}</button>
            <a href="{{ route('quotes.show', $quote) }}"
               class="tap inline-flex items-center rounded-lg border border-line-strong px-4 text-sm font-semibold">{{ __('Back') }}</a>
        </div>

        <div class="card p-8">
            <div class="flex items-start justify-between gap-6 border-b border-line pb-6">
                <div>
                    <p class="text-lg font-bold">{{ $mc['legal_name'] }}</p>
                    <p class="mt-1 text-xs text-text-muted">
                        {{ $mc['address']['line1'] }}, {{ $mc['address']['line2'] }}<br>
                        {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}<br>
                        {{ $mc['phone'] }} · {{ $mc['email'] }}
                    </p>
                </div>
                <div class="text-end">
                    <p class="text-sm font-bold uppercase">{{ __('Quotation') }}</p>
                    <p class="mt-1 text-xs text-text-muted tabular-nums">
                        {{ $quote->reference }}<br>
                        {{ $quote->created_at->format('j F Y') }}
                        @if ($quote->valid_until)
                            <br>{{ __('Valid until :date', ['date' => $quote->valid_until->format('j F Y')]) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid gap-6 border-b border-line py-6 sm:grid-cols-2">
                <div>
                    <p class="label-sm">{{ __('For') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ $quote->customer_name }}</p>
                    @if ($quote->phone)<p class="text-xs text-text-muted">{{ $quote->phone }}</p>@endif
                    @if ($quote->email)<p class="text-xs text-text-muted">{{ $quote->email }}</p>@endif
                </div>
                <div>
                    <p class="label-sm">{{ __('Event') }}</p>
                    <p class="mt-1 text-sm">
                        {{ $quote->event_date?->format('l j F Y') ?? __('Date to confirm') }}<br>
                        {{ trans_choice('{1}1 guest|[2,*]:count guests', $quote->guests, ['count' => $quote->guests]) }}
                        @if ($quote->venue)<br>{{ $quote->venue }}@endif
                    </p>
                </div>
            </div>

            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="label-sm border-b border-line text-start">
                        <th class="pb-2 text-start">{{ __('Item') }}</th>
                        <th class="pb-2 text-end">{{ __('Qty') }}</th>
                        <th class="pb-2 text-end">{{ __('Each') }}</th>
                        <th class="pb-2 text-end">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($quote->lines as $line)
                        <tr>
                            <td class="py-2.5">{{ $line->description }}</td>
                            <td class="py-2.5 text-end tabular-nums">{{ qty($line->quantity, 2) }} {{ $line->unit }}</td>
                            <td class="py-2.5 text-end tabular-nums">£{{ number_format($line->unit_price / 100, 2) }}</td>
                            <td class="py-2.5 text-end font-medium tabular-nums">£{{ number_format($line->line_total / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-6 ms-auto max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between text-text-muted">
                    <span>{{ __('Subtotal') }}</span>
                    <span class="tabular-nums">£{{ number_format($quote->subtotal / 100, 2) }}</span>
                </div>
                @if ($quote->discount_percent > 0)
                    <div class="flex justify-between text-text-muted">
                        <span>{{ __('Discount (:n%)', ['n' => $quote->discount_percent]) }}</span>
                        <span class="tabular-nums">−£{{ number_format($quote->discountAmount() / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-line pt-1.5 text-base font-bold">
                    <span>{{ __('Total') }}</span>
                    <span class="tabular-nums">£{{ number_format($quote->total / 100, 2) }}</span>
                </div>
                @if ($vat = vat_breakdown($quote->total))
                    <div class="flex justify-between text-text-muted">
                        <span>{{ __('Of which VAT at :rate%', ['rate' => $vat['rate']]) }}</span>
                        <span class="tabular-nums">{{ money($vat['vat']) }}</span>
                    </div>
                @endif
            </div>

            @if ($quote->terms)
                <div class="mt-8 border-t border-line pt-6">
                    <p class="label-sm">{{ __('Terms') }}</p>
                    <p class="mt-2 text-xs whitespace-pre-line text-text-muted">{{ $quote->terms }}</p>
                </div>
            @endif

            <p class="mt-8 text-center text-xs text-text-faint">
                {{ __('All catering is :halal.', ['halal' => $mc['halal']]) }} · {{ $mc['tagline'] }}
            </p>
        </div>
    </div>
</body>
</html>
