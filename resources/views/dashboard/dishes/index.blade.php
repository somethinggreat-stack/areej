@extends('layouts.app')

@section('title', __('Menu & recipes'))

@section('content')
    <x-page-head :title="__('Menu & recipes')"
                 :subtitle="__('What we cook, what it sells for, and what goes into it')">
        <x-btn :href="route('dishes.create')" icon="plus">{{ __('Add dish') }}</x-btn>
    </x-page-head>

    @if ($withoutRecipe > 0)
        <div class="card mb-5 flex flex-wrap items-center justify-between gap-3 border-warn/35 p-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-warn">
                    {{ trans_choice('{1}1 dish has no recipe|[2,*]:count dishes have no recipe', $withoutRecipe, ['count' => $withoutRecipe]) }}
                </p>
                <p class="text-xs text-text-muted">
                    {{ __('Without a recipe we cannot work out ingredients for a guest count, food cost, or margin.') }}
                </p>
            </div>
            <x-btn size="sm" variant="secondary" :href="route('dishes', ['filter' => 'no-recipe'])">{{ __('Show them') }}</x-btn>
        </div>
    @endif

    <x-filters :reset="route('dishes')">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[11rem] flex-1" />
        <x-field name="course" type="select" :label="__('Course')" :value="$course" class="min-w-[9rem]"
                 :options="['' => __('All courses')] + \App\Models\Dish::courseLabels()" />
    </x-filters>

    <x-tabs param="filter" :current="$filter" :tabs="[
        '' => __('In use').' ('.$total.')',
        'no-recipe' => __('Missing a recipe').' ('.$withoutRecipe.')',
        'archived' => __('Archived'),
    ]" />

    @if ($dishes->isEmpty())
        <x-empty icon="chef" :title="__('No dishes yet')"
                 :body="__('Add the dishes you cook so quotes can be priced and ingredients worked out automatically.')">
            <x-btn :href="route('dishes.create')" icon="plus">{{ __('Add the first dish') }}</x-btn>
        </x-empty>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($dishes as $dish)
                @php $margin = $dish->marginPercent(); @endphp
                <a href="{{ route('dishes.show', $dish) }}" class="card flex flex-col p-4 transition-colors hover:border-line-strong hover:bg-surface-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-text">{{ $dish->displayName() }}</p>
                            <p class="text-xs text-text-faint">{{ $dish->courseLabel() }}</p>
                        </div>
                        @if ($dish->is_signature)
                            <x-badge tone="gold">{{ __('Signature') }}</x-badge>
                        @endif
                    </div>

                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div>
                            @if (auth()->user()->canSeeFinancials())
                                <p class="text-lg font-semibold text-text tabular-nums">£{{ number_format($dish->priceInPounds(), 2) }}</p>
                                <p class="text-[0.68rem] text-text-faint">{{ __('per head') }}</p>
                            @endif
                        </div>

                        <div class="text-end">
                            @if ($dish->ingredients_count === 0)
                                <x-badge tone="warn">{{ __('No recipe') }}</x-badge>
                            @elseif ($margin !== null && auth()->user()->canSeeFinancials())
                                <p class="text-sm font-semibold tabular-nums {{ $margin < 40 ? 'text-warn' : 'text-good' }}">{{ $margin }}%</p>
                                <p class="text-[0.68rem] text-text-faint">{{ __('margin') }}</p>
                            @else
                                <x-badge>{{ trans_choice('{1}1 ingredient|[2,*]:count ingredients', $dish->ingredients_count, ['count' => $dish->ingredients_count]) }}</x-badge>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @if ($dish->is_vegetarian)<x-badge tone="good">{{ __('V') }}</x-badge>@endif
                        @if ($dish->contains_dairy)<x-badge>{{ __('Milk') }}</x-badge>@endif
                        @if ($dish->contains_nuts)<x-badge tone="bad">{{ __('Nuts') }}</x-badge>@endif
                        @unless ($dish->is_active)<x-badge>{{ __('Archived') }}</x-badge>@endunless
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-5">{{ $dishes->links() }}</div>
    @endif
@endsection
