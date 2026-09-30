@extends('layouts.app')

@section('title', __('Payments'))

@section('content')
    <x-page-head :title="__('Payments')" :subtitle="__('Every payment received, newest first')" />

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field name="from" type="date" :label="__('From')" :value="$from->toDateString()" />
        <x-field name="to" type="date" :label="__('To')" :value="$to->toDateString()" />
        <x-btn variant="secondary" type="submit">{{ __('Show') }}</x-btn>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <x-stat :label="__('Received in this period')" :value="money($received)" tone="good" icon="pound" />
        <x-stat :label="__('Payments')" :value="$payments->count()" />
    </div>

    @if ($payments->isEmpty())
        <x-empty icon="receipt" :title="__('No payments in these dates')" />
    @else
        <x-table :head="[__('Date'), __('Customer'), __('Order'), __('How'), __('Amount')]" :align="[4 => 'end']">
            @foreach ($payments as $payment)
                <tr @if ($payment->order) data-row-href="{{ route('order-book.edit', $payment->order) }}" @endif class="cursor-pointer transition-colors hover:bg-surface-2">
                    <td class="px-4 py-3 whitespace-nowrap">{{ $payment->paid_on->format('D j M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-text">{{ $payment->order?->customer_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $payment->order?->reference }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ \App\Models\OrderPayment::methodLabels()[$payment->method] ?? $payment->method }}</td>
                    <td class="px-4 py-3 text-end font-semibold tabular-nums {{ $payment->kind === 'refund' ? 'text-bad' : 'text-text' }}">{{ $payment->kind === 'refund' ? '−' : '' }}{{ money($payment->amount) }}</td>
                </tr>
            @endforeach
        </x-table>
    @endif
@endsection
