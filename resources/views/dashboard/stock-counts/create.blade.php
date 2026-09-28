@extends('layouts.app')

@section('title', __('Start a count'))

@section('content')
    @if ($openDraft)
        <div class="card mb-5 border-warn/30 bg-warn-bg p-4">
            <p class="text-sm font-semibold text-warn">{{ __('There is already a count in progress') }}</p>
            <p class="mt-1 text-xs text-warn">
                {{ __('Finish or submit :ref before starting another, or you will be counting the same shelves twice.', ['ref' => $openDraft->reference]) }}
            </p>
            <div class="mt-3">
                <x-btn variant="secondary" :href="route('stock-counts.show', $openDraft)">{{ __('Open it') }}</x-btn>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('stock-counts.store') }}" class="max-w-xl">
        @csrf

        <div class="card space-y-5 p-5">
            <x-field name="counted_on" type="date" :label="__('Date of the count')" required
                     :value="now()->toDateString()" />

            <fieldset>
                <legend class="block text-xs font-semibold text-text-muted">{{ __('What are you counting?') }}</legend>
                <div class="mt-2 space-y-2">
                    @foreach ([
                        ['weekly', __('Everything'), __('The full Monday sheet'), $weeklyCount],
                        ['daily', __('Daily items only'), __('The fast movers you watch between Mondays'), $dailyCount],
                        ['per_event', __('Before a job'), __('Items checked ahead of every event'), $perEventCount],
                    ] as [$value, $label, $hint, $n])
                        <label class="tap flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 transition-colors hover:bg-surface-2 has-checked:border-gold has-checked:bg-gold/8">
                            <input type="radio" name="scope" value="{{ $value }}" @checked($loop->first)
                                   class="mt-0.5 size-4 border-line-strong text-gold focus:ring-gold/40">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-text">{{ $label }}</span>
                                <span class="block text-xs text-text-muted">{{ $hint }}</span>
                                <span class="mt-1 block text-xs font-semibold text-text-faint">
                                    {{ trans_choice('{0}No items|{1}1 item|[2,*]:count items', $n, ['count' => $n]) }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('scope')
                    <p class="mt-1 text-xs font-medium text-bad">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-field name="notes" type="textarea" :label="__('Notes')" :rows="2" />
        </div>

        <div class="mt-5 flex gap-2">
            <x-btn type="submit">{{ __('Build the sheet') }}</x-btn>
            <x-btn variant="ghost" :href="route('stock-counts')">{{ __('Cancel') }}</x-btn>
        </div>
    </form>
@endsection
