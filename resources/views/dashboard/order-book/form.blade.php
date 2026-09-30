@extends('layouts.app')

@php
    $editing = $order->exists;
    $status = old('order_status', match ($order->status) { 'completed' => 'completed', 'cancelled' => 'cancelled', default => 'pending' });
    $inputClass = 'tap w-full rounded-lg border border-line-strong bg-surface px-3 py-2 text-sm text-text outline-none focus:border-royal-lit focus:ring-2 focus:ring-royal-lit/25';
@endphp

@section('title', $editing ? $order->customer_name : __('New order'))
@section('subtitle', $editing ? $order->reference.' · '.$order->event_date->format('l j F Y') : __('Type it in straight from the notebook'))

@section('content')
    <x-page-head :title="$editing ? $order->customer_name : __('New order')" :back="route('orders')" :backLabel="__('Orders')"
                 :subtitle="$editing ? $order->reference : __('Everything for one order on one page. Only the name and date are required.')">
        @if ($editing)
            <x-btn variant="ghost" size="sm" :href="route('orders.invoice', $order)">{{ __('Invoice') }}</x-btn>
            <x-btn variant="ghost" size="sm" :href="route('orders.show', $order)">{{ __('Kitchen view') }}</x-btn>
            <x-btn variant="secondary" size="sm" :href="route('order-book.create', ['date' => $order->event_date->toDateString()])" icon="plus">{{ __('New order') }}</x-btn>
        @endif
    </x-page-head>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-bad/30 bg-bad-bg px-4 py-3 text-sm font-medium text-bad">
            {{ __('Please check the fields marked in red.') }}
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('order-book.update', $order) : route('order-book.store') }}" class="grid gap-5 lg:grid-cols-[1fr_22rem]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="min-w-0 space-y-5">
            {{-- Customer and date --}}
            <section class="card space-y-4 p-5">
                <h2 class="text-sm font-semibold text-text">{{ __('Customer') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="customer_name" :label="__('Name')" :value="$order->customer_name" required autofocus />
                    <x-field name="phone" type="tel" :label="__('Phone')" :value="$order->phone" />
                </div>
                <x-field name="venue_address" type="textarea" :rows="2" :label="__('Address or other details')" :value="$order->venue_address" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="event_date" type="date" :label="__('Order date')" :value="old('event_date', $order->event_date?->toDateString())" required />
                    <div>
                        <p class="mb-1.5 text-xs font-semibold text-text-muted">{{ __('Status') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['pending' => __('Pending'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $value => $label)
                                <label class="tap flex cursor-pointer items-center gap-2 rounded-lg border border-line-strong px-3 text-sm text-text has-checked:border-gold has-checked:bg-gold/10">
                                    <input type="radio" name="order_status" value="{{ $value }}" @checked($status === $value) class="size-4">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- Items --}}
            <section class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Items') }}</h2>
                    <p class="text-xs text-text-muted">{{ __('Type each item with its quantity. The price is optional — leave it empty and type the order amount instead.') }}</p>
                </div>
                <div class="table-scroll">
                    <table class="w-full min-w-[38rem] text-sm" data-item-rows>
                        <thead class="border-b border-line bg-surface-2">
                            <tr class="label-sm">
                                <th class="px-4 py-2.5 text-start">{{ __('Item') }}</th>
                                <th class="w-24 px-2 py-2.5 text-start">{{ __('Qty') }}</th>
                                <th class="w-32 px-2 py-2.5 text-start">{{ __('Unit') }}</th>
                                <th class="w-28 px-2 py-2.5 text-start">{{ __('Price each') }}</th>
                                <th class="w-24 px-4 py-2.5 text-end">{{ __('Line') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (old('items', $rows) as $i => $row)
                                <tr data-item-row>
                                    <td class="px-4 py-2">
                                        <input name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" placeholder="{{ __('e.g. Chicken pulao') }}" aria-label="{{ __('Item') }}" class="{{ $inputClass }}">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input name="items[{{ $i }}][quantity]" type="number" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" aria-label="{{ __('Quantity') }}" data-qty class="{{ $inputClass }} tabular-nums">
                                    </td>
                                    <td class="px-2 py-2">
                                        <select name="items[{{ $i }}][unit]" aria-label="{{ __('Unit') }}" class="{{ $inputClass }}">
                                            @foreach ($units as $value => $label)
                                                <option value="{{ $value }}" @selected(($row['unit'] ?? 'portion') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input name="items[{{ $i }}][price]" type="number" step="0.01" min="0" value="{{ $row['price'] ?? '' }}" placeholder="£" aria-label="{{ __('Price each') }}" data-price class="{{ $inputClass }} tabular-nums">
                                    </td>
                                    <td class="px-4 py-2 text-end text-sm text-text-muted tabular-nums" data-line>—</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line bg-surface-2 px-5 py-3">
                    <button type="button" data-add-item class="tap inline-flex items-center gap-2 rounded-lg px-2 text-sm font-semibold text-link hover:underline">
                        <x-icon name="plus" class="size-4" /> {{ __('Add another item') }}
                    </button>
                </div>
            </section>

            <x-field name="notes" type="textarea" :rows="2" :label="__('Notes (optional)')" :value="$order->notes" />
        </div>

        {{-- Money --}}
        <aside class="space-y-5">
            <section class="card space-y-4 p-5">
                <h2 class="text-sm font-semibold text-text">{{ __('Money') }}</h2>
                <x-field name="total" type="number" step="0.01" min="0" prefix="£" :label="__('Order amount')"
                         :value="old('total', $editing && $order->total_amount ? number_format($order->total_amount / 100, 2, '.', '') : '')"
                         :hint="__('Leave empty to add up the priced items')" data-total />

                @if ($editing)
                    <dl class="grid grid-cols-2 gap-2 rounded-lg bg-surface-2 p-3 text-sm">
                        <dt class="text-text-muted">{{ __('Paid') }}</dt>
                        <dd class="text-end font-semibold text-good tabular-nums">{{ money($order->paidAmount()) }}</dd>
                        <dt class="text-text-muted">{{ __('Still owed') }}</dt>
                        <dd class="text-end font-semibold tabular-nums {{ $order->balanceAmount() > 0 ? 'text-bad' : 'text-text' }}">{{ money($order->balanceAmount()) }}</dd>
                        <dt class="text-text-muted">{{ __('Payment') }}</dt>
                        <dd class="text-end"><x-badge :tone="match ($order->paymentStatus()) { 'paid' => 'good', 'part_paid' => 'warn', 'unpaid' => 'bad', default => 'neutral' }">{{ $order->paymentStatusLabel() }}</x-badge></dd>
                    </dl>
                @endif

                <div class="space-y-3 border-t border-line pt-4">
                    <p class="text-xs font-semibold text-text-muted">{{ $editing ? __('Record a payment') : __('Paid so far') }}</p>
                    <x-field name="paid_now" type="number" step="0.01" min="0" prefix="£" :label="__('Amount')" :value="old('paid_now')" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-field name="paid_method" type="select" :label="__('How')" :value="old('paid_method', 'cash')"
                                 :options="['cash' => __('Cash'), 'bank_transfer' => __('Bank transfer'), 'card' => __('Card'), 'cheque' => __('Cheque'), 'other' => __('Other')]" />
                        <x-field name="paid_on" type="date" :label="__('When')" :value="old('paid_on', today()->toDateString())" />
                    </div>
                </div>
            </section>

            <div class="flex flex-col gap-2">
                <x-btn type="submit" class="w-full">{{ $editing ? __('Save changes') : __('Save order') }}</x-btn>
                @unless ($editing)
                    <x-btn type="submit" variant="secondary" name="add_another" value="1" class="w-full">{{ __('Save and add the next one') }}</x-btn>
                @endunless
            </div>
        </aside>
    </form>

    @if ($editing && $order->payments->isNotEmpty())
        <section class="card mt-5 overflow-hidden lg:max-w-[calc(100%-23.25rem)]">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Payment history') }}</h2>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($order->payments->sortByDesc('paid_on') as $payment)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="min-w-0 text-sm">
                            <span class="block font-medium text-text">{{ $payment->paid_on->format('D j M Y') }} · {{ \App\Models\OrderPayment::methodLabels()[$payment->method] ?? $payment->method }}</span>
                            <span class="block text-xs text-text-muted">{{ \App\Models\OrderPayment::kindLabels()[$payment->kind] ?? $payment->kind }}@if ($payment->recorder) · {{ $payment->recorder->name }}@endif</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="text-sm font-semibold tabular-nums {{ $payment->kind === 'refund' ? 'text-bad' : 'text-text' }}">{{ $payment->kind === 'refund' ? '−' : '' }}{{ money($payment->amount) }}</span>
                            <form method="POST" action="{{ route('payments.destroy', $payment) }}">
                                @csrf
                                @method('DELETE')
                                <x-btn type="submit" variant="ghost" size="sm" :confirm="__('Remove this payment?')">{{ __('Remove') }}</x-btn>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (! $editing && $recent->isNotEmpty())
        <section class="card mt-5 overflow-hidden lg:max-w-[calc(100%-23.25rem)]">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Just entered') }}</h2>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($recent as $entered)
                    <li>
                        <a href="{{ route('order-book.edit', $entered) }}" class="tap flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                            <span class="min-w-0 text-sm">
                                <span class="block truncate font-medium text-text">{{ $entered->customer_name }}</span>
                                <span class="block text-xs text-text-muted">{{ $entered->reference }} · {{ $entered->event_date->format('D j M') }}</span>
                            </span>
                            <span class="shrink-0 text-end text-sm tabular-nums">
                                <span class="block font-semibold text-text">{{ money($entered->total_amount) }}</span>
                                @if ($entered->balanceAmount() > 0)
                                    <span class="block text-xs text-bad">{{ __(':amount owed', ['amount' => money($entered->balanceAmount())]) }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
