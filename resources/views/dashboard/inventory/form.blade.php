@extends('layouts.app')

@section('title', $item->exists ? __('Edit :name', ['name' => $item->name_en]) : __('Add item'))

@section('content')
    <form method="POST" action="{{ $item->exists ? route('inventory.update', $item) : route('inventory.store') }}" class="max-w-2xl">
        @csrf
        @if ($item->exists)
            @method('PATCH')
        @endif

        <div class="card space-y-5 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name_en" :label="__('Name (English)')" :value="$item->name_en" required />
                <x-field name="name_ur" :label="__('Name (Urdu)')" :value="$item->name_ur" dir="rtl" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="inventory_category_id" type="select" :label="__('Category')" required
                         :value="$item->inventory_category_id"
                         :options="$categories->pluck('name_en', 'id')->all()" />
                <x-field name="supplier_id" type="select" :label="__('Supplier')"
                         :value="$item->supplier_id"
                         :options="['' => __('Not set')] + $suppliers->pluck('name', 'id')->all()" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="unit" :label="__('Unit')" :value="$item->unit" required :hint="__('kg, litres, boxes')" />
                <x-field name="sku" :label="__('Code')" :value="$item->sku" />
                <x-field name="count_frequency" type="select" :label="__('Count how often')" required
                         :value="$item->count_frequency"
                         :hint="__('Weekly items appear on the Monday sheet')"
                         :options="[
                            'weekly' => __('Weekly'),
                            'daily' => __('Daily'),
                            'per_event' => __('Before every job'),
                         ]" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @unless ($item->exists)
                    <x-field name="current_quantity" type="number" step="0.001" min="0"
                             :label="__('Opening stock')" :value="0"
                             :hint="__('What is on the shelf today')" />
                @endunless
                <x-field name="reorder_level" type="number" step="0.001" min="0" required
                         :label="__('Reorder at')" :value="$item->reorder_level"
                         :hint="__('Alert below this')" />
                <x-field name="reorder_quantity" type="number" step="0.001" min="0"
                         :label="__('Usual order size')" :value="$item->reorder_quantity" />
            </div>

            @if (auth()->user()->canSeeFinancials())
                <x-field name="unit_cost" type="number" step="0.01" min="0" suffix="£"
                         :label="__('Cost per :unit', ['unit' => $item->unit ?: __('unit')])"
                         :value="$item->unit_cost" />
            @endif

            <x-field name="notes" type="textarea" :label="__('Notes')" :value="$item->notes" />

            <x-field name="is_active" type="checkbox" :label="__('Status')"
                     :value="$item->exists ? $item->is_active : true"
                     :hint="__('In use')" />
        </div>

        <div class="mt-5 flex gap-2">
            <x-btn type="submit">{{ $item->exists ? __('Save changes') : __('Add item') }}</x-btn>
            <x-btn variant="ghost" :href="route('inventory')">{{ __('Cancel') }}</x-btn>
        </div>
    </form>
@endsection
