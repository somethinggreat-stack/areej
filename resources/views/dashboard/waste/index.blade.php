@extends('layouts.app')

@section('title', __('Waste'))
@section('subtitle', $from->format('j M') . ' – ' . $to->format('j M Y'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div>
            <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
                <x-field name="from" type="date" :label="__('From')" :value="$from->toDateString()" />
                <x-field name="to" type="date" :label="__('To')" :value="$to->toDateString()" />
                <x-field name="type" type="select" :label="__('Type')" :value="$type"
                         :options="['' => __('All types')] + \App\Models\WasteLog::typeLabels()" />
                <x-btn variant="secondary" type="submit">{{ __('Show') }}</x-btn>
            </form>

            @if ($byType->isNotEmpty())
                <div class="card mb-5 p-5">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-sm font-semibold text-text">{{ __('By type') }}</h2>
                        @if (auth()->user()->canSeeFinancials())
                            <p class="text-sm font-semibold text-text tabular-nums">£{{ number_format($totalCost, 2) }}</p>
                        @endif
                    </div>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($byType as $typeKey => $summary)
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <a href="{{ route('waste', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'type' => $typeKey]) }}"
                                   class="{{ $type === $typeKey ? 'font-semibold text-text' : 'text-text-muted hover:text-text' }}">
                                    {{ \App\Models\WasteLog::typeLabels()[$typeKey] ?? $typeKey }}
                                </a>
                                <span class="tabular-nums text-text-muted">
                                    {{ trans_choice('{1}1 entry|[2,*]:count entries', $summary['count'], ['count' => $summary['count']]) }}
                                    @if (auth()->user()->canSeeFinancials())
                                        · <span class="font-semibold text-text">£{{ number_format($summary['cost'], 2) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($byReason->isNotEmpty())
                <div class="card mb-5 p-5">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-sm font-semibold text-text">{{ __('Why we lost it') }}</h2>
                    </div>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($byReason as $reason => $summary)
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="text-text-muted">{{ \App\Models\WasteLog::reasonLabels()[$reason] ?? $reason }}</span>
                                <span class="tabular-nums text-text-muted">
                                    {{ trans_choice('{1}1 entry|[2,*]:count entries', $summary['count'], ['count' => $summary['count']]) }}
                                    @if (auth()->user()->canSeeFinancials())
                                        · <span class="font-semibold text-text">£{{ number_format($summary['cost'], 2) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($logs->isEmpty())
                <x-empty :title="__('Nothing recorded in this period')" :body="__('That is either very good news, or nobody is logging it.')" />
            @else
                <div class="card overflow-hidden">
                    <ul class="divide-y divide-line">
                        @foreach ($logs as $log)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-text">{{ $log->itemName() }}</span>
                                    <span class="block truncate text-xs text-text-muted">
                                        {{ $log->wasted_on->format('j M') }} · {{ $log->typeLabel() }} · {{ $log->reasonLabel() }}
                                        @if ($log->order) · {{ $log->order->customer_name }} @endif
                                        @if ($log->logger) · {{ $log->logger->name }} @endif
                                    </span>
                                </span>
                                <span class="shrink-0 text-end">
                                    <span class="block text-sm font-semibold tabular-nums text-bad">
                                        {{ qty($log->quantity) }} {{ $log->unitLabel() }}
                                    </span>
                                    @if (auth()->user()->canSeeFinancials())
                                        <span class="block text-xs text-text-faint tabular-nums">£{{ number_format($log->cost(), 2) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mt-5">{{ $logs->links() }}</div>
            @endif
        </div>

        <form method="POST" action="{{ route('waste.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            <h2 class="text-sm font-semibold text-text">{{ __('Log waste') }}</h2>
            <p class="text-xs text-text-muted">{{ __('Choosing a stock item takes it off the shelf too. For leftover cooked food, leave the stock item empty and write what it was.') }}</p>

            <x-field name="type" type="select" :label="__('Type')" required
                     :options="['' => __('Choose a type')] + \App\Models\WasteLog::typeLabels()" />
            <x-field name="inventory_item_id" type="select" :label="__('Stock item')"
                     :options="['' => __('Not a stock item')] + $items->mapWithKeys(fn ($i) => [$i->id => $i->displayName() . ' (' . qty($i->current_quantity) . ' ' . unit_label($i->unit) . ')'])->all()" />
            <x-field name="description" :label="__('Or what was it')" :hint="__('e.g. Chicken biryani left over from Saturday')" />
            <div class="grid grid-cols-2 gap-3">
                <x-field name="quantity" type="number" step="0.001" min="0" :label="__('How much')" required />
                <x-field name="unit" type="select" :label="__('Unit')"
                         :options="catering_units()" :hint="__('Not a stock item only')" />
            </div>
            <x-field name="reason" type="select" :label="__('Reason')" required
                     :options="\App\Models\WasteLog::reasonLabels()" />
            <x-field name="wasted_on" type="date" :label="__('When')" required :value="now()->toDateString()" />
            @if ($orders->isNotEmpty())
                <x-field name="order_id" type="select" :label="__('Against a job')"
                         :options="['' => __('Not job specific')] + $orders->mapWithKeys(fn ($o) => [$o->id => $o->customer_name . ' — ' . $o->event_date->format('j M')])->all()" />
            @endif
            <x-field name="notes" type="textarea" :rows="2" :label="__('Notes')" />
            <x-btn type="submit">{{ __('Record it') }}</x-btn>
        </form>
    </div>
@endsection
