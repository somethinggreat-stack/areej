@extends('layouts.app')

@section('title', __('Take an order'))

@section('content')
    <form method="POST" action="{{ route('orders.store') }}" class="max-w-3xl">
        @csrf

        <div class="card space-y-5 p-5">
            <h2 class="text-sm font-semibold text-text">{{ __('Customer') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="customer_name" :label="__('Name')" required />
                <x-field name="phone" type="tel" :label="__('Phone')" />
                <x-field name="email" type="email" :label="__('Email')" />
            </div>
        </div>

        <div class="card mt-4 space-y-5 p-5">
            <h2 class="text-sm font-semibold text-text">{{ __('The job') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="event_date" type="date" :label="__('Date')" required :value="$order->event_date?->toDateString()" />
                <x-field name="serve_time" type="time" :label="__('Serving time')" />
                <x-field name="guests" type="number" min="0" :label="__('Guests')" :value="0" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="order_type" :label="__('Occasion')" :hint="__('Wedding, Khatam Shareef, corporate…')" />
                <x-field name="service_style" type="select" :label="__('Service')" required
                         :value="$order->service_style"
                         :options="\App\Models\Order::serviceStyleLabels()" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="venue" :label="__('Venue')" />
                <x-field name="staff_required" type="number" min="0" :label="__('Staff needed')" :value="0" />
            </div>
            <x-field name="venue_address" type="textarea" :rows="2" :label="__('Venue address')" />
            <x-field name="menu_notes" type="textarea" :rows="4" :label="__('Menu')" :hint="__('What they have asked for. Free text — priced lines can be added afterwards.')" />
            <x-field name="dietary" type="textarea" :rows="2" :label="__('Dietary requirements')" />
        </div>

        @if (auth()->user()->canSeeFinancials())
            <div class="card mt-4 space-y-5 p-5">
                <h2 class="text-sm font-semibold text-text">{{ __('Money') }}</h2>
                <p class="text-xs text-text-muted">{{ __('Leave at zero if no price has been agreed yet.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="total_amount" type="number" step="0.01" min="0" suffix="£" :label="__('Total agreed')" :value="0" />
                    <x-field name="deposit_due" type="number" step="0.01" min="0" suffix="£" :label="__('Deposit due')" :value="0" />
                </div>
            </div>
        @endif

        <div class="card mt-4 space-y-5 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="status" type="select" :label="__('Status')" required :value="$order->status"
                         :options="\App\Models\Order::statusLabels()" />
                <x-field name="source" type="select" :label="__('Came in by')" :value="$order->source"
                         :options="[
                            'phone' => __('Phone'),
                            'whatsapp' => __('WhatsApp'),
                            'walk_in' => __('Walk in'),
                            'website' => __('Website'),
                            'referral' => __('Referral'),
                         ]" />
            </div>
            <x-field name="notes" type="textarea" :rows="3" :label="__('Internal notes')" />
        </div>

        <div class="mt-5 flex gap-2">
            <x-btn type="submit">{{ __('Save order') }}</x-btn>
            <x-btn variant="ghost" :href="route('orders')">{{ __('Cancel') }}</x-btn>
        </div>
    </form>
@endsection
