@extends('layouts.app')

@section('title', __('Diary'))
@section('subtitle', $month->translatedFormat('F Y'))

@section('content')
    <x-page-head :title="$month->translatedFormat('F Y')" :subtitle="__('Every job in the month')">
        <x-btn variant="secondary" size="sm" :href="route('calendar', ['month' => $month->subMonth()->toDateString()])">
            <x-icon name="arrow-left" class="size-4 rtl:rotate-180" />
            <span class="sr-only">{{ __('Previous month') }}</span>
        </x-btn>
        <x-btn variant="secondary" size="sm" :href="route('calendar')">{{ __('Today') }}</x-btn>
        <x-btn variant="secondary" size="sm" :href="route('calendar', ['month' => $month->addMonth()->toDateString()])">
            <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
            <span class="sr-only">{{ __('Next month') }}</span>
        </x-btn>
        <x-btn :href="route('orders.create')" icon="plus">{{ __('Take an order') }}</x-btn>
    </x-page-head>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Jobs this month')" :value="$jobCount" icon="calendar" />
        <x-stat :label="__('Guests')" :value="number_format($guestCount)" icon="users" />
        @if (auth()->user()->canSeeFinancials())
            <x-stat :label="__('Value')" :value="'£'.number_format($monthValue / 100, 2)" icon="pound" :href="route('reports')" />
        @endif
    </div>

    {{-- Month grid on desktop. A caterer needs to see clashes and quiet weeks,
         which a list cannot show. --}}
    <div class="card hidden overflow-hidden lg:block">
        <div class="grid grid-cols-7 border-b border-line bg-surface-2">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                <div class="label-sm px-3 py-2.5 text-center">{{ __($weekday) }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($days as $day)
                @php
                    $isToday = $day['date']->isToday();
                    $isWeekend = $day['date']->isWeekend();
                @endphp

                <div class="min-h-28 border-b border-e border-line p-2 last:border-e-0
                            {{ $day['in_month'] ? ($isWeekend ? 'bg-surface-2/60' : '') : 'bg-surface-2/40' }}">
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="grid size-6 place-items-center rounded-full text-xs font-semibold tabular-nums
                                     {{ $isToday ? 'bg-gold text-ink' : ($day['in_month'] ? 'text-text' : 'text-text-faint') }}">
                            {{ $day['date']->format('j') }}
                        </span>
                        @if ($day['orders']->count() > 1)
                            <span class="text-[0.62rem] font-semibold text-text-faint tabular-nums">{{ $day['orders']->count() }}</span>
                        @endif
                    </div>

                    <div class="space-y-1">
                        @foreach ($day['orders']->take(3) as $order)
                            <a href="{{ route('orders.show', $order) }}"
                               title="{{ $order->customer_name }} — {{ $order->guests }} {{ __('guests') }}"
                               class="block truncate rounded px-1.5 py-1 text-[0.68rem] font-medium transition-colors
                                      {{ match ($order->status) {
                                          'cancelled' => 'bg-bad-bg text-bad line-through',
                                          'completed', 'delivered' => 'bg-good-bg text-good',
                                          'draft' => 'bg-surface-3 text-text-muted',
                                          default => 'bg-info-bg text-info',
                                      } }} hover:opacity-80">
                                @if ($order->serve_time)
                                    <span class="tabular-nums">{{ \Illuminate\Support\Str::substr($order->serve_time, 0, 5) }}</span>
                                @endif
                                {{ $order->customer_name }}
                            </a>
                        @endforeach

                        @if ($day['orders']->count() > 3)
                            <p class="px-1.5 text-[0.62rem] text-text-faint">
                                {{ __('+:n more', ['n' => $day['orders']->count() - 3]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- A month grid is unusable on a phone, so the same data becomes a list. --}}
    <div class="space-y-3 lg:hidden">
        @php $withJobs = collect($days)->filter(fn ($d) => $d['in_month'] && $d['orders']->isNotEmpty()); @endphp

        @if ($withJobs->isEmpty())
            <x-empty icon="calendar" :title="__('Nothing booked this month')"
                     :body="__('Accepted quotes and orders taken by phone both land here.')">
                <x-btn :href="route('orders.create')" icon="plus">{{ __('Take an order') }}</x-btn>
            </x-empty>
        @else
            @foreach ($withJobs as $day)
                <section class="card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-line bg-surface-2 px-4 py-2.5">
                        <span class="text-sm font-semibold text-text">{{ $day['date']->translatedFormat('D j M') }}</span>
                        @if ($day['date']->isToday())
                            <x-badge tone="gold">{{ __('Today') }}</x-badge>
                        @endif
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($day['orders'] as $order)
                            <li>
                                <a href="{{ route('orders.show', $order) }}" class="tap flex items-center gap-3 px-4 py-3">
                                    <span class="w-12 shrink-0 text-xs font-semibold text-text-muted tabular-nums">
                                        {{ $order->serve_time ? \Illuminate\Support\Str::substr($order->serve_time, 0, 5) : '—' }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-text">{{ $order->customer_name }}</span>
                                        <span class="block truncate text-xs text-text-muted">
                                            {{ trans_choice('{1}1 guest|[2,*]:count guests', $order->guests, ['count' => $order->guests]) }}
                                            @if ($order->venue) · {{ $order->venue }} @endif
                                        </span>
                                    </span>
                                    <x-badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-badge>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
@endsection
