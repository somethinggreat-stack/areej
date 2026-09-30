@extends('layouts.app')

@section('title', $name)

@section('content')
    <x-page-head :title="$name" :subtitle="$phone" :back="route('customers')" :backLabel="__('Customers')" />

    <div class="mb-5 grid gap-4 sm:grid-cols-4">
        <x-stat :label="__('Orders')" :value="$orders->count()" icon="clipboard" />
        <x-stat :label="__('Total')" :value="money($total)" />
        <x-stat :label="__('Paid')" :value="money($paid)" tone="good" />
        <x-stat :label="__('Owed')" :value="money($owed)" :tone="$owed ? 'bad' : 'neutral'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="min-w-0">
            <h2 class="label-sm mb-3">{{ __('Orders') }}</h2>
            <x-table :head="[__('Date'), __('Order'), __('Status'), __('Total'), __('Owed')]" :align="[3 => 'end', 4 => 'end']">
                @foreach ($orders as $order)
                    <tr data-row-href="{{ route('order-book.edit', $order) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $order->event_date->format('D j M Y') }}</td>
                        <td class="px-4 py-3"><a href="{{ route('order-book.edit', $order) }}" class="font-medium text-text">{{ $order->reference }}</a></td>
                        <td class="px-4 py-3">
                            <x-badge :tone="$order->status === 'completed' ? 'good' : ($order->status === 'cancelled' ? 'bad' : 'info')">
                                {{ $order->status === 'completed' ? __('Completed') : ($order->status === 'cancelled' ? __('Cancelled') : __('Pending')) }}
                            </x-badge>
                            <x-badge :tone="match ($order->paymentStatus()) { 'paid' => 'good', 'part_paid' => 'warn', 'unpaid' => 'bad', default => 'neutral' }">{{ $order->paymentStatusLabel() }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ money($order->total_amount) }}</td>
                        <td class="px-4 py-3 text-end font-semibold tabular-nums {{ $order->balanceAmount() ? 'text-bad' : 'text-text-faint' }}">{{ money($order->balanceAmount()) }}</td>
                    </tr>
                @endforeach
            </x-table>
        </section>

        <section class="card h-fit overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Payment history') }}</h2>
            </div>
            @if ($payments->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('No payments yet.') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($payments as $row)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="min-w-0 text-sm">
                                <span class="block font-medium text-text">{{ $row['payment']->paid_on->format('j M Y') }}</span>
                                <span class="block text-xs text-text-muted">{{ $row['order']->reference }} · {{ \App\Models\OrderPayment::methodLabels()[$row['payment']->method] ?? $row['payment']->method }}</span>
                            </span>
                            <span class="text-sm font-semibold tabular-nums">{{ $row['payment']->kind === 'refund' ? '−' : '' }}{{ money($row['payment']->amount) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
