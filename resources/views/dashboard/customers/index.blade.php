@extends('layouts.app')

@section('title', __('Customers'))

@section('content')
    <x-page-head :title="__('Customers')" :subtitle="__('Worked out from the orders — same phone number, same customer')" />

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Customers')" :value="$customers->count()" icon="users" />
        <x-stat :label="__('Owing money')" :value="$customers->where('owed', '>', 0)->count()" :tone="$customers->where('owed', '>', 0)->count() ? 'warn' : 'neutral'" />
        <x-stat :label="__('Total owed')" :value="money($owedTotal)" :tone="$owedTotal ? 'bad' : 'neutral'" />
    </div>

    <x-filters :reset="route('customers')">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[12rem] flex-1" placeholder="{{ __('Name or phone') }}" />
    </x-filters>

    <x-tabs param="filter" :current="$filter" :tabs="['' => __('All'), 'owing' => __('Owing'), 'paid' => __('Fully paid')]" />

    @if ($customers->isEmpty())
        <x-empty icon="users" :title="__('No customers here yet')" :body="__('Customers appear as soon as their first order is entered.')" />
    @else
        <x-table :head="[__('Customer'), __('Orders'), __('Total'), __('Paid'), __('Owed'), __('Last order'), '']" :align="[1 => 'end', 2 => 'end', 3 => 'end', 4 => 'end']">
            @foreach ($customers as $customer)
                <tr data-row-href="{{ route('customers.show', $customer['key']) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                    <td class="px-4 py-3">
                        <a href="{{ route('customers.show', $customer['key']) }}" class="block font-medium text-text">{{ $customer['name'] }}</a>
                        @if ($customer['phone'])<span class="block text-xs text-text-muted">{{ $customer['phone'] }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-end tabular-nums">{{ $customer['orders'] }}</td>
                    <td class="px-4 py-3 text-end tabular-nums">{{ money($customer['total']) }}</td>
                    <td class="px-4 py-3 text-end tabular-nums text-good">{{ money($customer['paid']) }}</td>
                    <td class="px-4 py-3 text-end font-semibold tabular-nums {{ $customer['owed'] ? 'text-bad' : 'text-text-faint' }}">{{ money($customer['owed']) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-text-muted">{{ $customer['last']->format('j M Y') }}</td>
                    <td class="px-4 py-3 text-end"><x-icon name="arrow-right" class="size-4 text-text-faint rtl:rotate-180" /></td>
                </tr>
            @endforeach
        </x-table>
    @endif
@endsection
