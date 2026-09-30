@extends('layouts.app')

@section('title', __('Inventory'))

@section('content')
    @php $money = auth()->user()->canSeeFinancials(); @endphp

    <x-page-head :title="__('Inventory')"
                 :subtitle="trans_choice('{0}Nothing on the shelves|{1}1 item|[2,*]:count items', $items->total(), ['count' => number_format($items->total())])">
        <x-btn variant="secondary" :href="route('stock-counts.create')" icon="check">{{ __('Start a count') }}</x-btn>
        <x-btn variant="secondary" :href="route('waste')" icon="trash">{{ __('Log waste') }}</x-btn>
        @if ($money)
            <x-btn :href="route('inventory.create')" icon="plus">{{ __('Add item') }}</x-btn>
        @endif
    </x-page-head>
    @include('dashboard.partials.export-button', ['sheet' => 'stock', 'import' => true])

    @if ($lowStockCount > 0)
        <a href="{{ route('purchase-orders') }}" class="card mb-5 flex items-center gap-3 border-warn/35 p-4 transition-colors hover:bg-surface-2">
            <x-icon name="alert" class="size-5 shrink-0 text-warn" />
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-warn">
                    {{ trans_choice('{1}1 item is at or below its reorder level|[2,*]:count items are at or below their reorder level', $lowStockCount, ['count' => $lowStockCount]) }}
                </span>
                <span class="block text-xs text-text-muted">{{ __('Purchasing groups them by supplier so one order covers the lot.') }}</span>
            </span>
            <x-icon name="arrow-right" class="size-4 shrink-0 text-text-faint rtl:rotate-180" />
        </a>
    @endif

    <x-filters :reset="route('inventory')">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[11rem] flex-1"
                 placeholder="{{ __('Name or code') }}" />
        <x-field name="category" type="select" :label="__('Category')" :value="request('category')" class="min-w-[10rem]"
                 :options="['' => __('All categories')] + $categories->mapWithKeys(fn ($c) => [$c->id => $c->displayName()])->all()" />
    </x-filters>

    <x-tabs param="filter" :current="$filter" :tabs="[
        '' => __('In use'),
        'low' => __('Needs reordering').' ('.$lowStockCount.')',
        'archived' => __('Archived'),
    ]" />

    @if ($items->isEmpty())
        <x-empty icon="box" :title="__('Nothing here yet')"
                 :body="__('Add your stock items, or import the spreadsheet you already keep.')">
            @if ($money)
                <x-btn :href="route('inventory.create')" icon="plus">{{ __('Add the first item') }}</x-btn>
            @endif
        </x-empty>
    @else
        {{-- Cards on a phone, a table from md up: the same data, laid out for
             the device rather than a shrunken table nobody can tap. --}}
        <div class="space-y-2 md:hidden">
            @foreach ($items as $item)
                <a href="{{ route('inventory.show', $item) }}" class="card tap flex items-center justify-between gap-3 p-4">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-text">{{ $item->displayName() }}</span>
                        <span class="block truncate text-xs text-text-muted">
                            {{ $item->category?->displayName() }} · {{ $item->supplier?->name ?? __('No supplier') }}
                        </span>
                    </span>
                    <span class="shrink-0 text-end">
                        <span class="block text-sm font-semibold tabular-nums {{ $item->isLowStock() ? 'text-bad' : 'text-text' }}">
                            {{ qty($item->current_quantity) }} {{ unit_label($item->unit) }}
                        </span>
                        @if ($item->isLowStock())<x-badge tone="bad">{{ __('Low') }}</x-badge>@endif
                    </span>
                </a>
            @endforeach
        </div>

        <div class="hidden md:block">
            <x-table :head="[__('Item'), __('Category'), __('Supplier'), __('On hand'), __('Reorder at'), $money ? __('Value') : '']"
                     :align="['start', 'start', 'start', 'end', 'end', 'end']">
                @foreach ($items as $item)
                    <tr data-row-href="{{ route('inventory.show', $item) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                        <td class="px-4 py-3">
                            <a href="{{ route('inventory.show', $item) }}" class="font-medium text-text hover:text-link">
                                {{ $item->displayName() }}
                            </a>
                            @if ($item->isLowStock())<x-badge tone="bad" class="ms-2">{{ __('Low') }}</x-badge>@endif
                        </td>
                        <td class="px-4 py-3 text-text-muted">{{ $item->category?->displayName() }}</td>
                        <td class="px-4 py-3 text-text-muted">{{ $item->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-end tabular-nums {{ $item->isLowStock() ? 'font-semibold text-bad' : 'text-text' }}">
                            {{ qty($item->current_quantity) }} {{ unit_label($item->unit) }}
                        </td>
                        <td class="px-4 py-3 text-end tabular-nums text-text-faint">{{ qty($item->reorder_level) }}</td>
                        @if ($money)
                            <td class="px-4 py-3 text-end tabular-nums text-text-muted">£{{ number_format($item->stockValue(), 2) }}</td>
                        @else
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </x-table>
        </div>

        <div class="mt-5">{{ $items->links() }}</div>
    @endif
@endsection
