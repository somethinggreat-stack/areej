@extends('layouts.app')

@section('title', $item->displayName())
@section('subtitle', ($item->category?->displayName() ?? '') . ' · ' . ($item->supplier?->name ?? __('No supplier set')))

@section('content')
    <x-page-actions>
        @if (auth()->user()->canSeeFinancials())
            <x-btn variant="secondary" :href="route('inventory.edit', $item)">{{ __('Edit') }}</x-btn>
        @endif
        <x-btn variant="ghost" :href="route('inventory')">{{ __('Back to inventory') }}</x-btn>
    </x-page-actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="label-sm">{{ __('On hand') }}</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums {{ $item->isLowStock() ? 'text-bad' : 'text-text' }}">
                {{ qty($item->current_quantity) }}<span class="ml-1 text-base font-medium text-text-faint">{{ unit_label($item->unit) }}</span>
            </p>
            @if ($item->isLowStock())
                <p class="mt-1 text-xs font-medium text-bad">{{ __('At or below the reorder level') }}</p>
            @endif
        </div>

        <div class="card p-5">
            <p class="label-sm">{{ __('Reorder at') }}</p>
            <p class="mt-2 text-3xl font-semibold text-text tabular-nums">{{ qty($item->reorder_level) }}</p>
            <p class="mt-1 text-xs text-text-faint">{{ __('Usually order :n', ['n' => qty($item->suggestedOrderQuantity())]) }}</p>
        </div>

        <div class="card p-5">
            <p class="label-sm">{{ __('Wasted this month') }}</p>
            <p class="mt-2 text-3xl font-semibold text-text tabular-nums">{{ qty($wasteThisMonth) }}</p>
        </div>

        @if (auth()->user()->canSeeFinancials())
            <div class="card p-5">
                <p class="label-sm">{{ __('Stock value') }}</p>
                <p class="mt-2 text-3xl font-semibold text-text tabular-nums">£{{ number_format($item->stockValue(), 2) }}</p>
                <p class="mt-1 text-xs text-text-faint">£{{ number_format((float) $item->unit_cost, 2) }} / {{ unit_label($item->unit) }}</p>
            </div>
        @endif
    </div>

    @if (auth()->user()->canSeeFinancials())
        <form method="POST" action="{{ route('inventory.adjust', $item) }}" class="card mt-6 p-5">
            @csrf
            <h2 class="text-sm font-semibold text-text">{{ __('Correct the stock') }}</h2>
            <p class="mt-1 text-xs text-text-muted">
                {{ __('For a split bag or a miscount spotted later. Counts and deliveries go through their own screens.') }}
            </p>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <x-field name="quantity_change" type="number" step="0.001"
                             :label="__('Change by')" :suffix="unit_label($item->unit)"
                             :hint="__('Use a minus to take away')" />
                </div>
                <div class="min-w-[14rem] flex-1">
                    <x-field name="notes" :label="__('Reason')" required />
                </div>
                <x-btn type="submit" variant="secondary">{{ __('Apply') }}</x-btn>
            </div>
        </form>
    @endif

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-text">{{ __('History') }}</h2>
            <p class="mt-0.5 text-xs text-text-muted">{{ __('Every change to this item, most recent first.') }}</p>
        </div>

        @if ($movements->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nothing recorded yet.') }}</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($movements as $movement)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-text">{{ $movement->typeLabel() }}</span>
                            <span class="block truncate text-xs text-text-muted">
                                {{ $movement->created_at->format('j M Y, H:i') }}
                                @if ($movement->recorder) · {{ $movement->recorder->name }} @endif
                                @if ($movement->notes) · {{ $movement->notes }} @endif
                            </span>
                        </span>
                        <span class="shrink-0 text-end">
                            <span class="block text-sm font-semibold tabular-nums {{ $movement->quantity_change > 0 ? 'text-good' : 'text-bad' }}">
                                {{ $movement->quantity_change > 0 ? '+' : '' }}{{ qty($movement->quantity_change) }}
                            </span>
                            <span class="block text-xs text-text-faint tabular-nums">
                                {{ __('to :n', ['n' => qty($movement->quantity_after)]) }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
