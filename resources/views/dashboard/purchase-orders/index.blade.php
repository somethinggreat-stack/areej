@extends('layouts.app')

@section('title', __('Purchase orders'))

@section('content')
    @if ($lowStock->isNotEmpty())
        <div class="card mb-6 overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Needs reordering') }}</h2>
                <p class="mt-0.5 text-xs text-text-muted">{{ __('Grouped by supplier so one order covers everything from them.') }}</p>
            </div>

            @foreach ($lowStock as $supplierName => $items)
                @php $supplierId = $items->first()->supplier_id; @endphp
                <form method="POST" action="{{ route('purchase-orders.store') }}" class="border-b border-line p-5 last:border-0">
                    @csrf
                    <input type="hidden" name="supplier_id" value="{{ $supplierId }}">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-text">{{ $supplierName }}</h3>
                        @if ($supplierId)
                            <x-btn type="submit" variant="secondary">{{ __('Create order') }}</x-btn>
                        @else
                            <x-badge tone="warn">{{ __('Set a supplier on these items first') }}</x-badge>
                        @endif
                    </div>

                    <ul class="mt-3 space-y-2">
                        @foreach ($items as $index => $item)
                            <li class="flex flex-wrap items-center gap-3 rounded-lg bg-surface-2 p-3">
                                <input type="hidden" name="items[{{ $index }}][inventory_item_id]" value="{{ $item->id }}">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-text">{{ $item->displayName() }}</span>
                                    <span class="block text-xs text-bad">
                                        {{ __(':have :unit left, reorder at :level', [
                                            'have' => qty($item->current_quantity),
                                            'unit' => $item->unit,
                                            'level' => qty($item->reorder_level),
                                        ]) }}
                                    </span>
                                </span>
                                <span class="w-28">
                                    <label class="sr-only" for="qty-{{ $item->id }}">{{ __('Order quantity') }}</label>
                                    <input id="qty-{{ $item->id }}" type="number" step="0.001" min="0" inputmode="decimal"
                                           name="items[{{ $index }}][quantity_ordered]"
                                           value="{{ qty($item->suggestedOrderQuantity()) }}"
                                           class="tap w-full rounded-lg border border-line-strong px-3 py-2 text-end text-sm tabular-nums outline-none focus:border-gold focus:ring-2 focus:ring-gold/30">
                                </span>
                                <span class="w-20 text-xs text-text-faint">{{ $item->unit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </form>
            @endforeach
        </div>
    @endif

    @if ($orders->isEmpty())
        <x-empty :title="__('No purchase orders yet')" :body="__('When stock drops below its reorder level it appears above, ready to turn into an order.')" />
    @else
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('All orders') }}</h2>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($orders as $order)
                    <li>
                        <a href="{{ route('purchase-orders.show', $order) }}" class="tap flex items-center justify-between gap-4 px-5 py-4 hover:bg-surface-2">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-text">{{ $order->supplier->name }}</span>
                                    <x-badge :tone="$order->status === 'received' ? 'good' : ($order->status === 'cancelled' ? 'bad' : 'warn')">
                                        {{ $order->statusLabel() }}
                                    </x-badge>
                                </span>
                                <span class="mt-0.5 block text-xs text-text-muted">
                                    {{ $order->reference }} ·
                                    {{ trans_choice('{1}1 line|[2,*]:count lines', $order->lines->count(), ['count' => $order->lines->count()]) }}
                                    @if ($order->expected_on) · {{ __('due :date', ['date' => $order->expected_on->format('j M')]) }} @endif
                                </span>
                            </span>
                            @if (auth()->user()->canSeeFinancials())
                                <span class="shrink-0 text-sm font-semibold text-text tabular-nums">£{{ number_format($order->total(), 2) }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
@endsection
