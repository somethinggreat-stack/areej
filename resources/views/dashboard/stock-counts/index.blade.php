@extends('layouts.app')

@section('title', __('Stock counts'))

@section('content')
    <x-page-actions>
        <x-btn :href="route('stock-counts.create')">{{ __('Start a count') }}</x-btn>
    </x-page-actions>

    @if ($openDraft)
        <div class="card mb-5 flex flex-wrap items-center justify-between gap-3 border-gold/40 bg-gold/8 p-4">
            <div>
                <p class="text-sm font-semibold text-text">{{ __('A count is in progress') }}</p>
                <p class="text-xs text-text-muted">
                    {{ $openDraft->reference }} · {{ $openDraft->counted_on->format('j M Y') }} · {{ __('opened by :name', ['name' => $openDraft->openedBy?->name]) }}
                </p>
            </div>
            <x-btn :href="route('stock-counts.show', $openDraft)">{{ __('Carry on counting') }}</x-btn>
        </div>
    @endif

    @if ($counts->isEmpty())
        <x-empty :title="__('No counts yet')"
                 :body="__('The Monday sheet is built for you from the items flagged to be counted weekly.')">
            <x-btn :href="route('stock-counts.create')">{{ __('Start the first count') }}</x-btn>
        </x-empty>
    @else
        <div class="card overflow-hidden">
            <ul class="divide-y divide-line">
                @foreach ($counts as $count)
                    <li>
                        <a href="{{ route('stock-counts.show', $count) }}" class="tap flex items-center justify-between gap-4 px-5 py-4 hover:bg-surface-2">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-text">{{ $count->counted_on->format('D j M Y') }}</span>
                                    @if ($count->isDraft())
                                        <x-badge tone="warn">{{ __('In progress') }}</x-badge>
                                    @else
                                        <x-badge tone="good">{{ __('Submitted') }}</x-badge>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-text-muted">
                                    {{ $count->reference }} ·
                                    {{ trans_choice('{1}1 line|[2,*]:count lines', $count->lines_count, ['count' => $count->lines_count]) }} ·
                                    {{ __('opened by :name', ['name' => $count->openedBy?->name ?? '—']) }}
                                </span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold text-text-faint">
                                {{ ucfirst($count->scope) === 'Weekly' ? __('Weekly') : __('Spot check') }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-5">{{ $counts->links() }}</div>
    @endif
@endsection
