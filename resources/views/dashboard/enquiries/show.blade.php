@extends('layouts.app')

@section('title', $enquiry->name)
@section('subtitle', $enquiry->reference.' · '.$enquiry->created_at->format('j M Y, H:i'))

@section('content')
    <x-page-head :title="$enquiry->name" :back="route('enquiries')" :backLabel="__('Enquiries')"
                 :subtitle="$enquiry->reference.' · '.$enquiry->created_at->diffForHumans()">
        @if ($enquiry->phone)
            <x-btn variant="secondary" size="sm" :href="'tel:'.preg_replace('/\s+/', '', $enquiry->phone)">{{ $enquiry->phone }}</x-btn>
        @endif
        @if ($enquiry->email)
            <x-btn variant="secondary" size="sm" :href="'mailto:'.$enquiry->email">{{ __('Email') }}</x-btn>
        @endif
    </x-page-head>

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="space-y-4">
            {{-- ------------------------------------------- what they asked --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-text">{{ __('What they asked for') }}</h2>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        __('Occasion') => $enquiry->event_type,
                        __('Date') => $enquiry->event_date?->format('l j F Y'),
                        __('Guests') => $enquiry->guests,
                        __('Venue') => $enquiry->venue,
                        __('Service') => $enquiry->service_style,
                        __('Dietary') => $enquiry->dietary,
                        __('Phone') => $enquiry->phone,
                        __('Email') => $enquiry->email,
                    ] as $label => $value)
                        @if ($value)
                            <div>
                                <dt class="label-sm">{{ $label }}</dt>
                                <dd class="mt-1 text-sm text-text">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                @if (! empty($enquiry->extras))
                    <div class="mt-4">
                        <p class="label-sm">{{ __('Also wanted') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($enquiry->extras as $extra)
                                <x-badge>{{ $extra }}</x-badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($enquiry->message)
                    <div class="mt-4 border-t border-line pt-4">
                        <p class="label-sm">{{ __('Their message') }}</p>
                        <p class="mt-1.5 text-sm whitespace-pre-line text-text-muted">{{ $enquiry->message }}</p>
                    </div>
                @endif
            </div>

            {{-- ---------------------------------------------- what happens next --}}
            @if ($enquiry->order)
                <div class="card flex flex-wrap items-center justify-between gap-3 border-good/35 p-5">
                    <div>
                        <p class="text-sm font-semibold text-good">{{ __('This became a job') }}</p>
                        <p class="text-xs text-text-muted">{{ $enquiry->order->reference }} · {{ $enquiry->order->statusLabel() }}</p>
                    </div>
                    <x-btn :href="route('orders.show', $enquiry->order)">{{ __('Open the job') }}</x-btn>
                </div>
            @else
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-text">{{ __('What next?') }}</h2>
                    <p class="mt-1 text-xs text-text-muted">
                        {{ __('Normally you price it up and send a quote. Go straight to a job only when the price is already agreed.') }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @can('handle-quotes')
                            <form method="POST" action="{{ route('enquiries.quote', $enquiry) }}">
                                @csrf
                                <x-btn type="submit" icon="file-text">{{ __('Raise a quote') }}</x-btn>
                            </form>
                        @endcan

                        <form method="POST" action="{{ route('enquiries.convert', $enquiry) }}">
                            @csrf
                            <x-btn type="submit" variant="secondary"
                                   :confirm="__('Create a job straight away, without a quote?')">
                                {{ __('Straight to a job') }}
                            </x-btn>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ----------------------------------------------------- quotes --}}
            @if ($enquiry->quotes->isNotEmpty() && auth()->user()->canHandleQuotes())
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Quotes raised') }}</h2>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($enquiry->quotes as $quote)
                            <li>
                                <a href="{{ route('quotes.show', $quote) }}" class="tap flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-text tabular-nums">{{ $quote->reference }}</span>
                                        <span class="block text-xs text-text-muted">{{ $quote->created_at->format('j M Y') }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-3">
                                        <x-money :pence="$quote->total" class="text-sm font-semibold" />
                                        <x-badge :tone="$quote->statusTone()">{{ $quote->statusLabel() }}</x-badge>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- ---------------------------------------------------------- handling --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('enquiries.update', $enquiry) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Handling') }}</h2>

                <x-field name="status" type="select" :label="__('Status')" required :value="$enquiry->status"
                         :options="[
                            'new' => __('New'),
                            'contacted' => __('Contacted'),
                            'quoted' => __('Quoted'),
                            'won' => __('Won'),
                            'lost' => __('Lost'),
                         ]" />

                <x-field name="assigned_to" type="select" :label="__('Assigned to')" :value="$enquiry->assigned_to"
                         :options="['' => __('Nobody yet')] + $team->pluck('name', 'id')->all()" />

                <x-field name="internal_notes" type="textarea" :rows="5" :label="__('Internal notes')" :value="$enquiry->internal_notes" />

                <x-btn type="submit">{{ __('Save') }}</x-btn>
            </form>

            @unless ($enquiry->order)
                <form method="POST" action="{{ route('enquiries.destroy', $enquiry) }}" class="card p-5">
                    @csrf
                    @method('DELETE')
                    <x-btn type="submit" variant="danger" class="w-full"
                           :confirm="__('Delete enquiry :ref?', ['ref' => $enquiry->reference])">
                        {{ __('Delete this enquiry') }}
                    </x-btn>
                </form>
            @endunless
        </div>
    </div>
@endsection
