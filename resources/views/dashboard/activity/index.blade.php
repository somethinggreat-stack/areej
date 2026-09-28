@extends('layouts.app')

@section('title', __('Activity'))
@section('subtitle', __('Who changed what'))

@section('content')
    <x-page-head :title="__('Activity')" :subtitle="__('A read-only record of every change')" />

    <x-filters :reset="route('activity')">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[11rem] flex-1" />
        <x-field name="user" type="select" :label="__('Person')" :value="request('user')" class="min-w-[9rem]"
                 :options="['' => __('Everyone')] + $team->pluck('name', 'id')->all()" />
        <x-field name="action" type="select" :label="__('Action')" :value="request('action')" class="min-w-[9rem]"
                 :options="['' => __('All actions')] + collect($actions)->mapWithKeys(fn ($a) => [$a => __(ucfirst($a))])->all()" />
    </x-filters>

    @if ($logs->isEmpty())
        <x-empty icon="activity" :title="__('Nothing logged yet')"
                 :body="__('Changes to jobs, stock, money and settings appear here as they happen.')" />
    @else
        <div class="card divide-y divide-line overflow-hidden">
            @foreach ($logs as $log)
                <div class="flex items-start gap-3 px-5 py-3">
                    <x-badge :tone="$log->tone()" class="mt-0.5 shrink-0">{{ __(ucfirst($log->action)) }}</x-badge>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-text">{{ $log->summary }}</p>
                        <p class="mt-0.5 text-xs text-text-faint">
                            {{ $log->user?->name ?? __('System') }} ·
                            <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('j M Y, H:i') }}</time>
                            @if ($log->subject_type) · {{ $log->subjectLabel() }} @endif
                        </p>

                        @if ($log->changes)
                            <details class="mt-1.5">
                                <summary class="cursor-pointer text-xs font-semibold text-link">{{ __('What changed') }}</summary>
                                <ul class="mt-1.5 space-y-0.5 text-xs text-text-muted">
                                    @foreach ($log->changes as $field => $change)
                                        <li class="tabular-nums">
                                            <span class="font-medium">{{ $field }}</span>:
                                            <span class="text-bad">{{ \Illuminate\Support\Str::limit((string) ($change['from'] ?? '—'), 40) }}</span>
                                            →
                                            <span class="text-good">{{ \Illuminate\Support\Str::limit((string) ($change['to'] ?? '—'), 40) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
@endsection
