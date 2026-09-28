@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <x-page-head :title="$supplier->name" :back="route('suppliers')" :backLabel="__('Suppliers')"
                 :subtitle="$supplier->contact_name ?: __('No contact name')" />

    @if (auth()->user()->canSeeFinancials())
        <div class="mb-5 grid gap-4 sm:grid-cols-3">
            <x-stat :label="__('Delivered to us')" :value="'£'.number_format($received / 100, 2)" icon="truck"
                    :hint="__('Value of stock received')" />
            <x-stat :label="__('Paid')" :value="'£'.number_format($paid / 100, 2)" tone="good" />
            <x-stat :label="__('Owed')" :value="'£'.number_format($owed / 100, 2)"
                    :tone="$owed > 0 ? 'warn' : 'neutral'" />
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="space-y-4">
            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Purchase history') }}</h2>
                </div>
                @if ($supplier->purchaseOrders->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nothing ordered from them yet.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($supplier->purchaseOrders->take(20) as $order)
                            <li>
                                <a href="{{ route('purchase-orders.show', $order) }}" class="tap flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-text tabular-nums">{{ $order->reference }}</span>
                                        <span class="block text-xs text-text-muted">
                                            {{ $order->ordered_on?->format('j M Y') }} ·
                                            {{ trans_choice('{1}1 line|[2,*]:count lines', $order->lines->count(), ['count' => $order->lines->count()]) }}
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-3">
                                        <x-badge :tone="$order->status === 'received' ? 'good' : ($order->status === 'cancelled' ? 'bad' : 'warn')">
                                            {{ $order->statusLabel() }}
                                        </x-badge>
                                        @if (auth()->user()->canSeeFinancials())
                                            <span class="text-sm font-semibold text-text tabular-nums">£{{ number_format($order->total(), 2) }}</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if (auth()->user()->canSeeFinancials())
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Payments to them') }}</h2>
                    </div>
                    @if ($supplier->payments->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-text-muted">{{ __('Nothing paid yet.') }}</p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($supplier->payments as $payment)
                                <li class="flex items-center justify-between gap-3 px-5 py-3">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-text">{{ $payment->paid_on->format('j M Y') }}</span>
                                        <span class="block truncate text-xs text-text-muted">
                                            {{ \App\Models\OrderPayment::methodLabels()[$payment->method] ?? $payment->method }}
                                            @if ($payment->reference) · {{ $payment->reference }} @endif
                                            @if ($payment->recorder) · {{ $payment->recorder->name }} @endif
                                        </span>
                                    </span>
                                    <x-money :pence="$payment->amount" tone="good" class="shrink-0 text-sm font-semibold" />
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('suppliers.payments.store', $supplier) }}" class="flex flex-wrap items-end gap-3 border-t border-line p-5">
                        @csrf
                        <x-field name="amount" type="number" step="0.01" min="0" prefix="£" :label="__('Amount')" required class="w-32" />
                        <x-field name="method" type="select" :label="__('How')" class="w-40"
                                 :options="\App\Models\OrderPayment::methodLabels()" />
                        <x-field name="paid_on" type="date" :label="__('When')" :value="now()->toDateString()" required class="w-44" />
                        <x-btn type="submit" variant="secondary">{{ __('Record payment') }}</x-btn>
                    </form>
                </div>
            @endif

            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">
                        {{ trans_choice('{0}No items from them|{1}1 item from them|[2,*]:count items from them', $items->count(), ['count' => $items->count()]) }}
                    </h2>
                </div>
                @if ($items->isNotEmpty())
                    <ul class="divide-y divide-line">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ route('inventory.show', $item) }}" class="tap flex items-center justify-between gap-3 px-5 py-2.5 transition-colors hover:bg-surface-2">
                                    <span class="min-w-0 truncate text-sm text-text">{{ $item->displayName() }}</span>
                                    <span class="shrink-0 text-sm tabular-nums {{ $item->isLowStock() ? 'font-semibold text-bad' : 'text-text-muted' }}">
                                        {{ qty($item->current_quantity) }} {{ $item->unit }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Details') }}</h2>
                <x-field name="name" :label="__('Name')" :value="$supplier->name" required />
                <x-field name="contact_name" :label="__('Contact')" :value="$supplier->contact_name" />
                <x-field name="phone" type="tel" :label="__('Phone')" :value="$supplier->phone" />
                <x-field name="email" type="email" :label="__('Email')" :value="$supplier->email" />
                <x-field name="address" type="textarea" :rows="2" :label="__('Address')" :value="$supplier->address" />
                <x-field name="order_method" type="select" :label="__('How we order')" required :value="$supplier->order_method"
                         :options="['phone' => __('Phone'), 'whatsapp' => __('WhatsApp'), 'email' => __('Email'), 'online' => __('Online'), 'in_person' => __('In person')]" />
                <x-field name="lead_time_days" type="number" min="0" :label="__('Lead time (days)')" :value="$supplier->lead_time_days" />
                <x-field name="notes" type="textarea" :rows="2" :label="__('Notes')" :value="$supplier->notes" />
                <x-field name="is_active" type="checkbox" :label="__('Status')" :value="$supplier->is_active" :hint="__('Still using them')" />
                <x-btn type="submit">{{ __('Save') }}</x-btn>
            </form>

            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="card p-5">
                @csrf
                @method('DELETE')
                <x-btn type="submit" variant="danger" class="w-full"
                       :confirm="__('Archive :name? Their purchase history is kept.', ['name' => $supplier->name])">
                    {{ __('Archive this supplier') }}
                </x-btn>
            </form>
        </div>
    </div>
@endsection
