@extends('layouts.app')

@section('title', __('Add dish'))

@section('content')
    <x-page-head :title="__('Add dish')" :back="route('dishes')" :backLabel="__('Menu')" />

    <form method="POST" action="{{ route('dishes.store') }}" class="max-w-2xl">
        @csrf

        <div class="card space-y-5 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name_en" :label="__('Name (English)')" required />
                <x-field name="name_ur" :label="__('Name (Urdu)')" dir="rtl" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="course" type="select" :label="__('Course')" required :value="$dish->course"
                         :options="\App\Models\Dish::courseLabels()" />
                @if (auth()->user()->canSeeFinancials())
                    <x-field name="price_per_head" type="number" step="0.01" min="0" prefix="£" :label="__('Price per head')" :value="0" />
                @endif
                <x-field name="portion_grams" type="number" min="10" suffix="g" :label="__('Portion size')" :value="$dish->portion_grams" required
                         :hint="__('Used to sanity-check quantities')" />
            </div>

            <x-field name="description" type="textarea" :rows="2" :label="__('Description')" />

            <fieldset>
                <legend class="mb-2 block text-xs font-semibold text-text-muted">{{ __('Flags') }}</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <x-field name="is_vegetarian" type="checkbox" :label="__('Vegetarian')" :hint="__('Vegetarian')" />
                    <x-field name="contains_dairy" type="checkbox" :label="__('Contains dairy')" :hint="__('Contains dairy')" />
                    <x-field name="contains_nuts" type="checkbox" :label="__('Contains nuts')" :hint="__('Contains nuts')" />
                    <x-field name="is_signature" type="checkbox" :label="__('Signature dish')" :hint="__('Signature dish')" />
                </div>
            </fieldset>

            <x-field name="is_active" type="checkbox" :label="__('Status')" :value="true" :hint="__('On the menu')" />
        </div>

        <div class="mt-5 flex gap-2">
            <x-btn type="submit">{{ __('Add dish') }}</x-btn>
            <x-btn variant="ghost" :href="route('dishes')">{{ __('Cancel') }}</x-btn>
        </div>
    </form>
@endsection
