@extends('layouts.app')

@section('title', $dish->displayName())
@section('subtitle', $dish->courseLabel())

@section('content')
    @php
        $cost = $dish->ingredientCostPerHead();
        $margin = $dish->marginPercent();
    @endphp

    <x-page-head :title="$dish->displayName()" :back="route('dishes')" :backLabel="__('Menu')"
                 :subtitle="$dish->courseLabel()" />

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Sells for')" :value="'£'.number_format($dish->priceInPounds(), 2)" :hint="__('per head')" />
        <x-stat :label="__('Ingredients cost')"
                :value="$cost === null ? '—' : '£'.number_format($cost / 100, 2)"
                :hint="$cost === null ? __('No recipe entered yet') : __('per head, at current prices')" />
        <x-stat :label="__('Gross margin')"
                :value="$margin === null ? '—' : $margin.'%'"
                :tone="$margin === null ? 'neutral' : ($margin < 40 ? 'warn' : 'good')"
                :hint="$margin === null ? __('Needs a recipe and a price') : null" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        {{-- ------------------------------------------------------- recipe --}}
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('Recipe') }}</h2>
                <p class="mt-0.5 text-xs text-text-muted">
                    {{ __('How much of each item feeds 100 people. This is what turns a guest count into a shopping list.') }}
                </p>
            </div>

            @if ($dish->ingredients->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-text-muted">
                    {{ __('No ingredients yet. Add them below and everything else follows automatically.') }}
                </p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($dish->ingredients as $ingredient)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-text">
                                    {{ $ingredient->inventoryItem?->displayName() ?? __('Removed item') }}
                                </span>
                                <span class="block text-xs text-text-faint">{{ $ingredient->inventoryItem?->category?->displayName() }}</span>
                            </span>
                            <span class="flex shrink-0 items-center gap-4">
                                <span class="text-end">
                                    <span class="block text-sm font-semibold text-text tabular-nums">
                                        {{ qty($ingredient->quantity_per_100) }} {{ $ingredient->inventoryItem?->unit }}
                                    </span>
                                    <span class="block text-[0.68rem] text-text-faint">{{ __('per 100 guests') }}</span>
                                </span>
                                <form method="POST" action="{{ route('dishes.ingredients.destroy', $ingredient) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-btn type="submit" size="sm" variant="ghost"
                                           :confirm="__('Remove this ingredient from the recipe?')">
                                        <x-icon name="trash" class="size-3.5 text-bad" />
                                        <span class="sr-only">{{ __('Remove') }}</span>
                                    </x-btn>
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ul>

                {{-- A worked example makes the per-100 figure concrete. --}}
                <div class="border-t border-line bg-surface-2 px-5 py-4">
                    <p class="label-sm">{{ __('For 300 guests you would need') }}</p>
                    <ul class="mt-2 grid gap-1 text-sm text-text-muted sm:grid-cols-2">
                        @foreach ($dish->ingredients as $ingredient)
                            <li class="tabular-nums">
                                {{ qty($ingredient->quantityFor(300)) }} {{ $ingredient->inventoryItem?->unit }}
                                <span class="text-text-faint">{{ $ingredient->inventoryItem?->displayName() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('dishes.ingredients.store', $dish) }}"
                  class="flex flex-wrap items-end gap-3 border-t border-line p-5">
                @csrf
                <x-field name="inventory_item_id" type="select" :label="__('Ingredient')" required class="min-w-[12rem] flex-1"
                         :options="$items->mapWithKeys(fn ($i) => [$i->id => $i->displayName().' ('.$i->unit.')'])->all()" />
                <x-field name="quantity_per_100" type="number" step="0.001" min="0" :label="__('Per 100 guests')" required class="w-36" />
                <x-btn type="submit" variant="secondary" icon="plus">{{ __('Add') }}</x-btn>
            </form>
        </div>

        {{-- ------------------------------------------------------ details --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('dishes.update', $dish) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Details') }}</h2>

                <x-field name="name_en" :label="__('Name (English)')" :value="$dish->name_en" required />
                <x-field name="name_ur" :label="__('Name (Urdu)')" :value="$dish->name_ur" dir="rtl" />
                <x-field name="course" type="select" :label="__('Course')" required :value="$dish->course"
                         :options="\App\Models\Dish::courseLabels()" />
                <x-field name="price_per_head" type="number" step="0.01" min="0" prefix="£" :label="__('Price per head')"
                         :value="$dish->priceInPounds()" />
                <x-field name="portion_grams" type="number" min="10" suffix="g" :label="__('Portion size')" :value="$dish->portion_grams" required />
                <x-field name="description" type="textarea" :rows="2" :label="__('Description')" :value="$dish->description" />

                <div class="grid gap-2">
                    <x-field name="is_vegetarian" type="checkbox" :label="__('Vegetarian')" :value="$dish->is_vegetarian" :hint="__('Vegetarian')" />
                    <x-field name="contains_dairy" type="checkbox" :label="__('Contains dairy')" :value="$dish->contains_dairy" :hint="__('Contains dairy')" />
                    <x-field name="contains_nuts" type="checkbox" :label="__('Contains nuts')" :value="$dish->contains_nuts" :hint="__('Contains nuts')" />
                    <x-field name="is_signature" type="checkbox" :label="__('Signature')" :value="$dish->is_signature" :hint="__('Signature dish')" />
                    <x-field name="is_active" type="checkbox" :label="__('Status')" :value="$dish->is_active" :hint="__('On the menu')" />
                </div>

                <x-btn type="submit">{{ __('Save') }}</x-btn>
            </form>

            <form method="POST" action="{{ route('dishes.destroy', $dish) }}" class="card p-5">
                @csrf
                @method('DELETE')
                <x-btn type="submit" variant="danger" class="w-full"
                       :confirm="__('Delete :name? Archive it instead if it might come back.', ['name' => $dish->displayName()])">
                    {{ __('Delete this dish') }}
                </x-btn>
            </form>
        </div>
    </div>
@endsection
