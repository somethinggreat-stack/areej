@extends('layouts.app')

@section('title', __('New quote'))

@section('content')
    <x-page-head :title="__('New quote')" :back="route('quotes')" :backLabel="__('Quotes')"
                 :subtitle="__('The details first, then what they are having')" />

    <form method="POST" action="{{ route('quotes.store') }}" class="max-w-3xl">
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
            <h2 class="text-sm font-semibold text-text">{{ __('The event') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="event_date" type="date" :label="__('Date')" :value="$quote->event_date?->toDateString()" />
                <x-field name="guests" type="number" min="1" :label="__('Guests')" :value="$quote->guests" required />
                <x-field name="event_type" :label="__('Occasion')" :hint="__('Wedding, Khatam Shareef…')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="venue" :label="__('Venue')" />
                <x-field name="service_style" type="select" :label="__('Service')" required
                         :value="$quote->service_style" :options="\App\Models\Order::serviceStyleLabels()" />
            </div>
        </div>

        <div class="card mt-4 space-y-5 p-5">
            <h2 class="text-sm font-semibold text-text">{{ __('Terms') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="discount_percent" type="number" min="0" max="100" suffix="%" :label="__('Discount')" :value="0" />
                <x-field name="valid_until" type="date" :label="__('Valid until')" :value="$quote->valid_until?->toDateString()" />
            </div>
            <x-field name="terms" type="textarea" :rows="3" :label="__('Terms shown on the quote')"
                     :hint="__('Deposit, cancellation, what is included')" />
            <x-field name="notes" type="textarea" :rows="2" :label="__('Internal notes')" />
        </div>

        <div class="mt-5 flex gap-2">
            <x-btn type="submit">{{ __('Create quote') }}</x-btn>
            <x-btn variant="ghost" :href="route('quotes')">{{ __('Cancel') }}</x-btn>
        </div>
    </form>
@endsection
