@extends('layouts.app')

@section('title', __('Settings'))
@section('subtitle', __('Business rules, changeable without a developer'))

@section('content')
    <x-page-head :title="__('Settings')" :subtitle="__('These figures drive quotes, alerts and pay across the whole system')" />

    <form method="POST" action="{{ route('settings.update') }}" class="max-w-3xl space-y-4">
        @csrf
        @method('PATCH')

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
