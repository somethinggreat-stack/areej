@extends('layouts.app')

@section('title', __('Count :ref', ['ref' => $count->reference]))
@section('subtitle', $count->counted_on->format('l j F Y'))

@section('content')
    @php $total = $lines->flatten()->count(); @endphp

    <div class="card mb-5 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-text">
                    {{ __(':done of :total counted', ['done' => $count->countedLines(), 'total' => $total]) }}
                </p>
                <p class="text-xs text-text-muted">
                    @if ($count->isDraft())
                        {{ __('Nothing moves until you submit. Save each number as you go.') }}
                    @else
                        {{ __('Submitted by :name on :date', ['name' => $count->completedBy?->name, 'date' => $count->completed_at?->format('j M Y, H:i')]) }}
                    @endif
                </p>
            </div>

            @if ($count->isDraft())
                <form method="POST" action="{{ route('stock-counts.complete', $count) }}"
                      onsubmit="return confirm('{{ __('Submit this count? Stock will be corrected to the numbers entered.') }}')">
                    @csrf
                    <x-btn type="submit">{{ __('Submit the count') }}</x-btn>
                </form>
            @endif
        </div>

        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-2">
            <div class="h-full rounded-full bg-gold transition-all" style="width: {{ $count->progress() }}%"></div>
        </div>
    </div>

    @error('count')
        <div class="card mb-5 border-bad/30 bg-bad-bg p-4 text-sm text-bad">{{ $message }}</div>
    @enderror

    @foreach ($lines as $categoryName => $categoryLines)
        <div class="card mb-4 overflow-hidden">
            <div class="border-b border-line bg-surface-2 px-4 py-2.5">
                <h2 class="label-sm">{{ $categoryName }}</h2>
            </div>

            <ul class="divide-y divide-line">
                @foreach ($categoryLines as $line)
                    <li class="p-4">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-text">{{ $line->item->displayName() }}</p>
                                <p class="text-xs text-text-muted">
                                    {{ __('System says :n :unit', [
                                        'n' => qty($line->expected_quantity),
                                        'unit' => $line->item->unit,
                                    ]) }}
                                    @if ($line->isCounted() && $line->variance() != 0)
                                        · <span class="font-semibold {{ $line->variance() > 0 ? 'text-good' : 'text-bad' }}">
                                            {{ $line->variance() > 0 ? '+' : '' }}{{ qty($line->variance()) }}
                                        </span>
                                    @endif
                                </p>
                            </div>

                            @if ($count->isDraft())
                                <form method="POST" action="{{ route('stock-counts.update', $count) }}" class="flex items-end gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="line_id" value="{{ $line->id }}">
                                    <div class="w-28">
                                        <label class="sr-only" for="line-{{ $line->id }}">
                                            {{ __('Counted quantity for :name', ['name' => $line->item->displayName()]) }}
                                        </label>
                                        <input id="line-{{ $line->id }}" type="number" step="0.001" min="0"
                                               name="counted_quantity" inputmode="decimal"
                                               value="{{ $line->counted_quantity !== null ? qty($line->counted_quantity) : '' }}"
                                               placeholder="—"
                                               class="tap w-full rounded-lg border border-line-strong px-3 py-2 text-end text-sm tabular-nums outline-none focus:border-gold focus:ring-2 focus:ring-gold/30">
                                    </div>
                                    <x-btn type="submit" variant="secondary">{{ __('Save') }}</x-btn>
                                </form>
                            @else
                                <p class="text-sm font-semibold tabular-nums text-text">
                                    {{ $line->isCounted() ? qty($line->counted_quantity) : __('Not counted') }}
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@endsection
