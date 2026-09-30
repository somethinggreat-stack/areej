@extends('layouts.app')

@section('title', __('Orders'))

@section('content')
    @php $money = auth()->user()->canSeeFinancials(); @endphp

    <x-page-head :title="__('Orders')" :subtitle="__('Every order, newest first. Open one to change it or record a payment.')">
        @if ($money)
            <x-btn variant="secondary" :href="route('customers')" icon="users">{{ __('Customers') }}</x-btn>
            <x-btn :href="route('order-book.create')" icon="plus">{{ __('Enter orders') }}</x-btn>
        @else
            <x-btn :href="route('orders.create')" icon="plus">{{ __('Take an order') }}</x-btn>
        @endif
    </x-page-head>
    @include('dashboard.partials.export-button', ['sheet' => 'orders'])

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Next 7 days')" :value="$thisWeek" icon="calendar" />
        @if ($money)
            <x-stat :label="__('Money outstanding')" :value="'£'.number_format($owingTotal / 100, 2)"
                    :tone="$owingTotal > 0 ? 'warn' : 'neutral'"
                    :hint="trans_choice('{0}All settled|{1}across 1 job|[2,*]across :count jobs', $owingCount, ['count' => $owingCount])"
                    :href="route('orders', ['view' => 'unpaid'])" />
        @endif
    </div>

    <x-filters :reset="route('orders')">
        <input type="hidden" name="view" value="{{ $view }}">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[12rem] flex-1"
                 placeholder="{{ __('Name, reference or phone') }}" />
        <x-field name="from" type="date" :label="__('From')" :value="request('from')" />
        <x-field name="to" type="date" :label="__('To')" :value="request('to')" />
    </x-filters>

    <x-tabs :current="$view" :tabs="[
        'all' => __('All'),
        'pending' => __('Pending'),
        'completed' => __('Completed'),
        'owing' => __('Owing'),
        'paid' => __('Paid'),
        'cancelled' => __('Cancelled'),
    ]" />

    @if ($orders->isEmpty())
        <x-empty icon="clipboard" :title="__('No jobs here')"
                 :body="__('Accepted quotes land here automatically. Orders taken by phone can be added by hand.')">
            <x-btn :href="$money ? route('order-book.create') : route('orders.create')" icon="plus">{{ __('Enter orders') }}</x-btn>
            @can('handle-quotes')
                <x-btn variant="secondary" :href="route('quotes')">{{ __('See quotes') }}</x-btn>
            @endcan
        </x-empty>
    @else
        <x-table :head="[__('Customer'), __('Date'), $money ? __('Paid') : __('Guests'), __('Status'), $money ? __('Total') : '', $money ? __('Owed') : '', '']"
                 :align="['start', 'start', 'end', 'start', 'end', 'end', 'end']">
            @foreach ($orders as $order)
                @php $open = $money ? route('order-book.edit', $order) : route('orders.show', $order); @endphp
                <tr data-row-href="{{ $open }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                    <td class="px-4 py-3">
                        <a href="{{ $open }}" class="font-medium text-text hover:text-link">
                            {{ $order->customer_name }}
                        </a>
                        <span class="block text-xs text-text-faint tabular-nums">{{ $order->reference }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-text-muted">
                        <span class="tabular-nums">{{ $order->event_date->format('D j M Y') }}</span>
                        @if ($order->venue)
                            <span class="block truncate text-xs text-text-faint">{{ $order->venue }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end tabular-nums text-text-muted">
                        @if ($money){{ money($order->paidAmount()) }}@else{{ $order->guests ?: '—' }}@endif
                    </td>
                    <td class="px-4 py-3">
                        <x-badge :tone="$order->status === 'completed' ? 'good' : ($order->status === 'cancelled' ? 'bad' : 'info')">
                            {{ $order->status === 'completed' ? __('Completed') : ($order->status === 'cancelled' ? __('Cancelled') : __('Pending')) }}
                        </x-badge>
                    </td>
                    @if ($money)
                        <td class="px-4 py-3 text-end font-semibold"><x-money :pence="$order->total_amount" /></td>
                        <td class="px-4 py-3 text-end">
                            @if ($order->balanceAmount() > 0)
                                <x-money :pence="$order->balanceAmount()" :tone="$order->event_date->isPast() ? 'bad' : 'warn'" class="font-semibold" />
                            @else
                                <x-badge tone="good">{{ __('Paid') }}</x-badge>
                            @endif
                        </td>
                    @else
                        <td></td><td></td>
                    @endif
                    <td class="px-4 py-3 text-end">
                        <x-icon name="arrow-right" class="ms-auto size-4 text-text-faint rtl:rotate-180" />
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
@endsection
