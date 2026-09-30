@extends('layouts.app')

@section('title', __('Weekly summary'))
@section('subtitle', $start->format('j M').' – '.$end->format('j M Y'))

@section('content')
    <x-page-head :title="__('Week of :date', ['date' => $start->format('l j F Y')])"
                 :subtitle="__('Orders dated Monday :from to Sunday :to', ['from' => $start->format('j M'), 'to' => $end->format('j M')])">
        <x-btn variant="secondary" size="sm" icon="download" :href="route('weekly-summary.export', ['week' => $start->toDateString()])" class="print:hidden">{{ __('Export to Excel') }}</x-btn>
        <x-btn variant="secondary" size="sm" type="button" onclick="window.print()" class="print:hidden">{{ __('Print') }}</x-btn>
    </x-page-head>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4 print:hidden">
        <x-field name="week" type="date" :label="__('Any day in the week')" :value="$start->toDateString()" />
        <x-btn variant="secondary" type="submit">{{ __('Show') }}</x-btn>
        <x-btn variant="ghost" :href="route('weekly-summary', ['week' => $start->subWeek()->toDateString()])" icon="arrow-left">{{ __('Previous week') }}</x-btn>
        <x-btn variant="ghost" :href="route('weekly-summary', ['week' => $start->addWeek()->toDateString()])">{{ __('Next week') }}</x-btn>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Orders')" :value="$orderCount" icon="clipboard" />
        <x-stat :label="__('Customers served')" :value="$customerCount" icon="users" />
        <x-stat :label="__('Total sales')" :value="money($sales)" icon="pound" />
    </div>
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Paid against these orders')" :value="money($paidOnOrders)" tone="good" />
        <x-stat :label="__('Still owed on these orders')" :value="money($owed)" :tone="$owed ? 'bad' : 'neutral'" />
        <x-stat :label="__('Money received this week')" :value="money($receivedThisWeek)" tone="good"
                :hint="__('All payments dated this week, for any order')" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Who still owes --}}
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Still owing') }}</h2>
                <span class="text-sm font-semibold text-bad tabular-nums">{{ money($owed) }}</span>
            </div>
            @if ($stillOwing->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nobody owes anything for this week.') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($stillOwing as $customer)
                        <li>
                            <a href="{{ route('customers.show', $customer['key']) }}" class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                                <span class="min-w-0 text-sm">
                                    <span class="block truncate font-medium text-text">{{ $customer['name'] }}</span>
                                    <span class="block text-xs text-text-muted">
                                        @if ($customer['phone']){{ $customer['phone'] }} · @endif{{ __(':paid paid of :total', ['paid' => money($customer['paid']), 'total' => money($customer['total'])]) }}
                                    </span>
                                </span>
                                <span class="shrink-0 text-sm font-semibold text-bad tabular-nums">{{ money($customer['owed']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Paid in full --}}
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Paid in full') }}</h2>
                <span class="text-sm font-semibold text-good tabular-nums">{{ $paidInFull->count() }}</span>
            </div>
            @if ($paidInFull->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('No one has paid in full for this week yet.') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($paidInFull as $customer)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-text">{{ $customer['name'] }}</span>
                                <span class="block text-xs text-text-muted">{{ $customer['phone'] }}</span>
                            </span>
                            <span class="shrink-0 font-semibold text-good tabular-nums">{{ money($customer['total']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Overdue, from any week --}}
    <section class="card mt-6 overflow-hidden {{ $overdue->isNotEmpty() ? 'border-bad/35' : '' }}">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold {{ $overdue->isNotEmpty() ? 'text-bad' : 'text-text' }}">{{ __('Overdue payments') }}</h2>
                <p class="text-xs text-text-muted">{{ __('Any order still unpaid :n days after its date — from this week or earlier.', ['n' => \App\Http\Controllers\Dashboard\WeeklySummaryController::OVERDUE_AFTER_DAYS]) }}</p>
            </div>
            <span class="text-sm font-semibold tabular-nums {{ $overdue->isNotEmpty() ? 'text-bad' : 'text-text-faint' }}">{{ money($overdueTotal) }}</span>
        </div>
        @if ($overdue->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nothing overdue.') }}</p>
        @else
            <div class="table-scroll">
                <table class="w-full min-w-[34rem] text-sm">
                    <thead class="border-b border-line bg-surface-2">
                        <tr class="label-sm">
                            <th class="px-4 py-2.5 text-start">{{ __('Order date') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('Customer') }}</th>
                            <th class="px-4 py-2.5 text-end">{{ __('Total') }}</th>
                            <th class="px-4 py-2.5 text-end">{{ __('Owed') }}</th>
                            <th class="px-4 py-2.5 text-end">{{ __('Days late') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($overdue as $order)
                            <tr data-row-href="{{ route('order-book.edit', $order) }}" class="cursor-pointer hover:bg-surface-2">
                                <td class="px-4 py-2.5 whitespace-nowrap">{{ $order->event_date->format('D j M Y') }}</td>
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('order-book.edit', $order) }}" class="font-medium text-text">{{ $order->customer_name }}</a>
                                    <span class="block text-xs text-text-muted">{{ $order->reference }} @if ($order->phone)· {{ $order->phone }}@endif</span>
                                </td>
                                <td class="px-4 py-2.5 text-end tabular-nums">{{ money($order->total_amount) }}</td>
                                <td class="px-4 py-2.5 text-end font-semibold text-bad tabular-nums">{{ money($order->balanceAmount()) }}</td>
                                <td class="px-4 py-2.5 text-end tabular-nums text-text-muted">{{ (int) $order->event_date->diffInDays(today()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- The week's orders --}}
    <section class="mt-6">
        <h2 class="label-sm mb-3">{{ __('All orders this week') }}</h2>
        @if ($orders->isEmpty())
            <x-empty icon="clipboard" :title="__('No orders for this week')" :body="__('Enter last week\'s orders from the notebook and they will be summed up here.')">
                <x-btn :href="route('order-book.create', ['date' => $start->toDateString()])" icon="plus" class="print:hidden">{{ __('Enter orders for this week') }}</x-btn>
            </x-empty>
        @else
            <x-table :head="[__('Date'), __('Customer'), __('Status'), __('Total'), __('Paid'), __('Owed')]" :align="[3 => 'end', 4 => 'end', 5 => 'end']">
                @foreach ($orders as $order)
                    <tr data-row-href="{{ route('order-book.edit', $order) }}" class="cursor-pointer hover:bg-surface-2">
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $order->event_date->format('D j M') }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('order-book.edit', $order) }}" class="font-medium text-text">{{ $order->customer_name }}</a>
                            <span class="block text-xs text-text-muted">{{ $order->reference }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            <x-badge :tone="$order->status === 'completed' ? 'good' : 'info'">{{ $order->status === 'completed' ? __('Completed') : __('Pending') }}</x-badge>
                        </td>
                        <td class="px-4 py-2.5 text-end tabular-nums">{{ money($order->total_amount) }}</td>
                        <td class="px-4 py-2.5 text-end tabular-nums text-good">{{ money($order->paidAmount()) }}</td>
                        <td class="px-4 py-2.5 text-end font-semibold tabular-nums {{ $order->balanceAmount() ? 'text-bad' : 'text-text-faint' }}">{{ money($order->balanceAmount()) }}</td>
                    </tr>
                @endforeach
            </x-table>
            @if ($noPrice->isNotEmpty())
                <p class="mt-3 text-xs text-warn">{{ trans_choice('{1}1 order has no amount yet, so it is not in the sales total.|[2,*]:count orders have no amount yet, so they are not in the sales total.', $noPrice->count(), ['count' => $noPrice->count()]) }}</p>
            @endif
        @endif
    </section>
@endsection
