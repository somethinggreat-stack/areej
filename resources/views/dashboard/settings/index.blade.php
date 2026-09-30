@extends('layouts.app')

@section('title', __('Settings'))
@section('subtitle', __('Business rules, changeable without a developer'))

@section('content')
    <x-page-head :title="__('Settings')" :subtitle="__('These figures drive quotes, alerts and pay across the whole system')" />

    <form method="POST" action="{{ route('settings.update') }}" class="max-w-3xl space-y-4">
        @csrf
        @method('PATCH')
        <input type="hidden" name="menu_present" value="1">

        <section class="card p-5">
            <h2 class="text-sm font-semibold text-text">{{ __('Menu') }}</h2>
            <p class="mt-1 text-xs text-text-muted">{{ __('Tick the sections the business uses. Unticked sections leave the menu for everyone, but nothing in them is deleted — tick one again to bring it back.') }}</p>

            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($menuChoices as $groupLabel => $items)
                    <fieldset>
                        <legend class="label-sm mb-2">{{ $groupLabel }}</legend>
                        <div class="space-y-1.5">
                            @foreach ($items as $item)
                                <label class="tap flex cursor-pointer items-center gap-2.5 text-sm text-text">
                                    <input type="checkbox" name="menu_shown[]" value="{{ $item['route'] }}"
                                           @checked(! in_array($item['route'], $menuHidden, true))
                                           class="size-4 shrink-0 rounded border-line-strong bg-surface text-gold focus:ring-2 focus:ring-gold/40">
                                    {{ $item['label'] }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>

        @foreach ($groups as $groupName => $rows)
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-text">
                    {{ ['menu' => __('Menu'), 'sales' => __('Sales & quotes'), 'stock' => __('Stock'), 'people' => __('People & pay'), 'money' => __('Money')][$groupName] ?? __(ucfirst($groupName)) }}
                </h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($rows as $row)
                        @if ($row['type'] === 'bool')
                            <x-field :name="$row['key']" type="checkbox" :label="__($row['label'])"
                                     :value="$row['value']" :hint="$row['hint'] ? __($row['hint']) : null" />
                        @else
                            <x-field :name="$row['key']" type="number" :label="__($row['label'])"
                                     :value="$row['value']" :suffix="$row['suffix'] ? __($row['suffix']) : null"
                                     :hint="$row['hint'] ? __($row['hint']) : null" required />
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach

        <x-btn type="submit">{{ __('Save settings') }}</x-btn>
    </form>
@endsection
