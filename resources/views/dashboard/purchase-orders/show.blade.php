@extends('layouts.app')

@section('title', $order->supplier->name)
@section('subtitle', $order->reference . ' · ' . $order->statusLabel())

@section('content')
    <x-page-actions>
        <x-btn variant="ghost" :href="route('purchase-orders')">{{ __('Back to orders') }}</x-btn>
    </x-page-actions>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('purchase-orders.receive', $order) }}" class="card overflow-hidden">
            @csrf
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Lines') }}</h2>
                <p class="mt-0.5 text-xs text-text-muted">{{ __('Enter what actually turned up. Short deliveries leave the order open.') }}</p>
            </div>

            <ul class="divide-y divide-line">
                @foreach ($order->lines as $index => $line)
                    <li class="p-5">
                        <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-text">{{ $line->item->displayName() }}</p>
                                <p class="text-xs text-text-muted">
                                    {{ __('Ordered :n :unit', ['n' => qty($line->quantity_ordered), 'unit' => unit_label($line->item->unit)]) }}
                                    · {{ __('received :n', ['n' => qty($line->quantity_received)]) }}
                                    @if ($line->outstanding() > 0)
                                        · <span class="font-semibold text-warn">{{ __(':n outstanding', ['n' => qty($line->outstanding())]) }}</span>
                                    @endif
                                </p>
                            </div>

                            @if ($order->isOpen())
                                <div class="flex items-end gap-2">
                                    <div class="w-24">
                                        <label class="block text-[0.68rem] font-semibold text-text-muted" for="recv-{{ $line->id }}">{{ __('Received') }}</label>
                                        <input id="recv-{{ $line->id }}" type="number" step="0.001" min="0" inputmode="decimal"
                                               name="lines[{{ $index }}][quantity_received]" value="0"
                                               class="tap mt-1 w-full rounded-lg border border-line-strong px-3 py-2 text-end text-sm tabular-nums outline-none focus:border-gold focus:ring-2 focus:ring-gold/30">
                                    </div>
                                    @if (auth()->user()->canSeeFinancials())
                                        <div class="w-24">
                                            <label class="block text-[0.68rem] font-semibold text-text-muted" for="cost-{{ $line->id }}">{{ __('£ / :unit', ['unit' => unit_label($line->item->unit)]) }}</label>
                                            <input id="cost-{{ $line->id }}" type="number" step="0.01" min="0" inputmode="decimal"
                                                   name="lines[{{ $index }}][unit_cost]" value="{{ $line->unit_cost }}"
                                                   class="tap mt-1 w-full rounded-lg border border-line-strong px-3 py-2 text-end text-sm tabular-nums outline-none focus:border-gold focus:ring-2 focus:ring-gold/30">
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($order->isOpen())
                <div class="flex flex-wrap items-end gap-3 border-t border-line bg-surface-2 p-5">
                    <div class="w-44">
                        <x-field name="received_on" type="date" :label="__('Delivery date')" :value="now()->toDateString()" required />
                    </div>
                    <x-btn type="submit">{{ __('Book the delivery in') }}</x-btn>
                </div>
            @endif
        </form>

        <div class="space-y-4">
            <form method="POST" action="{{ route('purchase-orders.update', $order) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Order details') }}</h2>
                <x-field name="status" type="select" :label="__('Status')" :value="$order->status"
                         :options="['draft' => __('Draft'), 'sent' => __('Sent to supplier'), 'cancelled' => __('Cancelled')]" />
                <x-field name="expected_on" type="date" :label="__('Expected')" :value="$order->expected_on?->toDateString()" />
                <x-field name="notes" type="textarea" :rows="3" :label="__('Notes')" :value="$order->notes" />
                <x-btn type="submit" variant="secondary">{{ __('Save') }}</x-btn>
            </form>

            @if (auth()->user()->canSeeFinancials())
                <div class="card p-5">
                    <p class="label-sm">{{ __('Order value') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-text tabular-nums">£{{ number_format($order->total(), 2) }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
