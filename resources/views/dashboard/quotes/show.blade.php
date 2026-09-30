@extends('layouts.app')

@section('title', $quote->customer_name)
@section('subtitle', $quote->reference.' · '.$quote->statusLabel())

@section('content')
    <x-page-head :title="$quote->customer_name" :back="route('quotes')" :backLabel="__('Quotes')"
                 :subtitle="$quote->reference.($quote->event_date ? ' · '.$quote->event_date->format('l j F Y') : '')">
        <x-btn variant="secondary" :href="route('quotes.print', $quote)" icon="file-text" target="_blank">{{ __('Print') }}</x-btn>

        @if ($quote->status === 'draft')
            <form method="POST" action="{{ route('quotes.send', $quote) }}">
                @csrf
                <x-btn type="submit" icon="arrow-right">{{ __('Mark as sent') }}</x-btn>
            </form>
        @endif

        @if ($quote->isEditable())
            <form method="POST" action="{{ route('quotes.accept', $quote) }}">
                @csrf
                <x-btn type="submit" variant="dark" icon="check">{{ __('Accepted — create the job') }}</x-btn>
            </form>
        @endif
    </x-page-head>

    @if ($quote->order)
        <div class="card mb-5 flex flex-wrap items-center justify-between gap-3 border-good/35 p-4">
            <div>
                <p class="text-sm font-semibold text-good">{{ __('This quote was accepted') }}</p>
                <p class="text-xs text-text-muted">{{ __('Job :ref is on the books.', ['ref' => $quote->order->reference]) }}</p>
            </div>
            <x-btn size="sm" :href="route('orders.show', $quote->order)">{{ __('Open the job') }}</x-btn>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="space-y-4">
            {{-- ------------------------------------------------------ lines --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('What they are having') }}</h2>
                    <span class="text-xs text-text-muted tabular-nums">
                        {{ trans_choice('{0}No lines|{1}1 line|[2,*]:count lines', $quote->lines->count(), ['count' => $quote->lines->count()]) }}
                    </span>
                </div>

                @if ($quote->lines->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">
                        {{ __('Nothing on the quote yet. Pick a dish below, or type a line for staffing or hire.') }}
                    </p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($quote->lines as $line)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-text">{{ $line->description }}</span>
                                    <span class="block text-xs text-text-muted tabular-nums">
                                        {{ qty($line->quantity, 2) }} {{ unit_label($line->unit) }} × £{{ number_format($line->unit_price / 100, 2) }}
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <x-money :pence="$line->line_total" class="text-sm font-semibold" />
                                    @if ($quote->isEditable())
                                        <form method="POST" action="{{ route('quotes.lines.destroy', $line) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-btn type="submit" size="sm" variant="ghost"
                                                   :confirm="__('Remove :line from this quote?', ['line' => $line->description])">
                                                <x-icon name="trash" class="size-3.5 text-bad" />
                                                <span class="sr-only">{{ __('Remove') }}</span>
                                            </x-btn>
                                        </form>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Totals --}}
                <div class="space-y-1.5 border-t border-line bg-surface-2 px-5 py-4 text-sm">
                    <div class="flex justify-between text-text-muted">
                        <span>{{ __('Subtotal') }}</span>
                        <x-money :pence="$quote->subtotal" />
                    </div>
                    @if ($quote->discount_percent > 0)
                        <div class="flex justify-between text-text-muted">
                            <span>{{ __('Discount (:n%)', ['n' => $quote->discount_percent]) }}</span>
                            <span class="tabular-nums">−<x-money :pence="$quote->discountAmount()" /></span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-line pt-1.5 text-base font-semibold text-text">
                        <span>{{ __('Total') }}</span>
                        <x-money :pence="$quote->total" />
                    </div>
                    @if ($vat = vat_breakdown($quote->total))
                        <p class="text-end text-xs text-text-faint">
                            {{ __('Includes VAT at :rate%: :vat', ['rate' => $vat['rate'], 'vat' => money($vat['vat'])]) }}
                        </p>
                    @endif
                    @if ($quote->guests > 0)
                        <p class="pt-1 text-end text-xs text-text-faint">
                            {{ __('£:n per head', ['n' => number_format($quote->perHeadInPounds(), 2)]) }}
                        </p>
                    @endif
                </div>

                @if ($quote->isEditable())
                    <form method="POST" action="{{ route('quotes.lines.store', $quote) }}"
                          class="flex flex-wrap items-end gap-3 border-t border-line p-5" data-line-scope>
                        @csrf
                        <x-field name="dish_id" type="select" :label="__('Dish')" class="min-w-[11rem] flex-1"
                                 :options="['' => __('Or type a line below')] + $dishes->mapWithKeys(fn ($d) => [$d->id => $d->displayName().' — £'.number_format($d->priceInPounds(), 2)])->all()" />
                        <x-field name="description" :label="__('Description')" class="min-w-[10rem] flex-1"
                                 placeholder="{{ __('Waiting staff, 4 hours') }}" />
                        <x-field name="quantity" type="number" step="0.01" min="0" :label="__('Qty')" :value="$quote->guests"
                                 class="w-24" data-line-qty required />
                        <x-field name="unit" type="select" :label="__('Unit')" value="portion" class="w-32"
                                 :options="catering_units()" />
                        <x-field name="unit_price" type="number" step="0.01" min="0" prefix="£" :label="__('Each')" :value="0"
                                 class="w-28" data-line-price required />
                        <div class="pb-2 text-sm font-semibold text-text-muted">
                            <span data-line-total class="tabular-nums">£0.00</span>
                        </div>
                        <x-btn type="submit" variant="secondary" icon="plus">{{ __('Add') }}</x-btn>
                    </form>
                @endif
            </div>

            @if ($quote->terms)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-text">{{ __('Terms') }}</h2>
                    <p class="mt-2 text-sm whitespace-pre-line text-text-muted">{{ $quote->terms }}</p>
                </div>
            @endif

            @if ($quote->isEditable())
                <div class="card flex flex-wrap items-center justify-between gap-3 p-5">
                    <div>
                        <p class="text-sm font-semibold text-text">{{ __('They said no?') }}</p>
                        <p class="text-xs text-text-muted">{{ __('Marking it declined keeps the record and closes the enquiry.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('quotes.decline', $quote) }}">
                        @csrf
                        <x-btn type="submit" variant="danger" :confirm="__('Mark this quote as declined?')">{{ __('Mark declined') }}</x-btn>
                    </form>
                </div>
            @endif
        </div>

        {{-- ------------------------------------------------------- details --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('quotes.update', $quote) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Details') }}</h2>

                <x-field name="customer_name" :label="__('Customer')" :value="$quote->customer_name" required :disabled="! $quote->isEditable()" />
                <x-field name="phone" type="tel" :label="__('Phone')" :value="$quote->phone" :disabled="! $quote->isEditable()" />
                <x-field name="email" type="email" :label="__('Email')" :value="$quote->email" :disabled="! $quote->isEditable()" />
                <x-field name="event_date" type="date" :label="__('Date')" :value="$quote->event_date?->toDateString()" :disabled="! $quote->isEditable()" />
                <x-field name="guests" type="number" min="1" :label="__('Guests')" :value="$quote->guests" required :disabled="! $quote->isEditable()" />
                <x-field name="event_type" :label="__('Occasion')" :value="$quote->event_type" :disabled="! $quote->isEditable()" />
                <x-field name="venue" :label="__('Venue')" :value="$quote->venue" :disabled="! $quote->isEditable()" />
                <x-field name="service_style" type="select" :label="__('Service')" required :value="$quote->service_style"
                         :options="\App\Models\Order::serviceStyleLabels()" :disabled="! $quote->isEditable()" />
                <x-field name="discount_percent" type="number" min="0" max="100" suffix="%" :label="__('Discount')"
                         :value="$quote->discount_percent" :disabled="! $quote->isEditable()" />
                <x-field name="valid_until" type="date" :label="__('Valid until')" :value="$quote->valid_until?->toDateString()" :disabled="! $quote->isEditable()" />
                <x-field name="terms" type="textarea" :rows="3" :label="__('Terms')" :value="$quote->terms" :disabled="! $quote->isEditable()" />
                <x-field name="notes" type="textarea" :rows="2" :label="__('Internal notes')" :value="$quote->notes" :disabled="! $quote->isEditable()" />

                @if ($quote->isEditable())
                    <x-btn type="submit">{{ __('Save') }}</x-btn>
                @endif
            </form>

            @if ($quote->status !== 'accepted')
                <form method="POST" action="{{ route('quotes.destroy', $quote) }}" class="card p-5">
                    @csrf
                    @method('DELETE')
                    <x-btn type="submit" variant="danger" class="w-full"
                           :confirm="__('Delete quote :ref? This cannot be undone.', ['ref' => $quote->reference])">
                        {{ __('Delete this quote') }}
                    </x-btn>
                </form>
            @endif
        </div>
    </div>
@endsection
