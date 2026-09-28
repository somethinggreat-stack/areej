@extends('layouts.app')

@section('title', __('Suppliers'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div>
            @if ($suppliers->isEmpty())
                <x-empty :title="__('No suppliers yet')" :body="__('Add the places you buy from so low stock can be grouped into one order each.')" />
            @else
                <div class="space-y-3">
                    @foreach ($suppliers as $supplier)
                        <details class="card p-4 {{ $supplier->is_active ? '' : 'opacity-60' }}">
                            <summary class="tap flex cursor-pointer list-none items-center justify-between gap-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-text">{{ $supplier->name }}</span>
                                    <span class="block truncate text-xs text-text-muted">
                                        {{ $supplier->contact_name ?: __('No contact name') }}
                                        @if ($supplier->phone) · {{ $supplier->phone }} @endif
                                        · {{ trans_choice('{0}No items|{1}1 item|[2,*]:count items', $supplier->inventory_items_count, ['count' => $supplier->inventory_items_count]) }}
                                    </span>
                                </span>
                                @unless ($supplier->is_active)
                                    <x-badge>{{ __('Inactive') }}</x-badge>
                                @endunless
                            </summary>

                            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="mt-4 space-y-4 border-t border-line pt-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-field name="name" :label="__('Name')" :value="$supplier->name" required />
                                    <x-field name="contact_name" :label="__('Contact')" :value="$supplier->contact_name" />
                                    <x-field name="phone" :label="__('Phone')" :value="$supplier->phone" type="tel" />
                                    <x-field name="email" :label="__('Email')" :value="$supplier->email" type="email" />
                                    <x-field name="order_method" type="select" :label="__('How we order')"
                                             :value="$supplier->order_method"
                                             :options="[
                                                'phone' => __('Phone'),
                                                'whatsapp' => __('WhatsApp'),
                                                'email' => __('Email'),
                                                'online' => __('Online'),
                                                'in_person' => __('In person'),
                                             ]" />
                                    <x-field name="lead_time_days" type="number" min="0" :label="__('Lead time (days)')" :value="$supplier->lead_time_days" />
                                </div>
                                <x-field name="notes" type="textarea" :rows="2" :label="__('Notes')" :value="$supplier->notes" />
                                <x-field name="is_active" type="checkbox" :label="__('Status')" :value="$supplier->is_active" :hint="__('Still using them')" />
                                <x-btn type="submit" variant="secondary">{{ __('Save') }}</x-btn>
                            </form>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('suppliers.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            <h2 class="text-sm font-semibold text-text">{{ __('Add a supplier') }}</h2>
            <x-field name="name" :label="__('Name')" required />
            <x-field name="contact_name" :label="__('Contact')" />
            <x-field name="phone" type="tel" :label="__('Phone')" />
            <x-field name="order_method" type="select" :label="__('How we order')"
                     :options="[
                        'phone' => __('Phone'),
                        'whatsapp' => __('WhatsApp'),
                        'email' => __('Email'),
                        'online' => __('Online'),
                        'in_person' => __('In person'),
                     ]" />
            <x-field name="lead_time_days" type="number" min="0" :label="__('Lead time (days)')" :value="1" />
            <x-btn type="submit">{{ __('Add supplier') }}</x-btn>
        </form>
    </div>
@endsection
