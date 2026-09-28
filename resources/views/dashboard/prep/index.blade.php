@extends('layouts.app')

@section('title', __('Prep board'))
@section('subtitle', __('Everything the kitchen owes, soonest first'))

@section('content')
    <x-page-head :title="__('Prep board')" :subtitle="__('Everything the kitchen owes, soonest first')" />

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Outstanding')" :value="$outstanding" icon="flame" />
        <x-stat :label="__('Overdue')" :value="$overdue" :tone="$overdue > 0 ? 'bad' : 'neutral'" icon="alert" />
        <x-stat :label="__('Done')" :value="$done" tone="good" icon="check-circle" />
    </div>

    <x-tabs param="days" :current="(string) $days" :tabs="[
        '1' => __('Today'),
        '3' => __('Next 3 days'),
        '7' => __('This week'),
        '14' => __('Next 2 weeks'),
    ]" />

    @if ($orders->isEmpty())
        <x-empty icon="check-circle" :title="__('Nothing to prep')"
                 :body="__('No jobs in this window. Widen the range or check the diary.')">
            <x-btn variant="secondary" :href="route('calendar')">{{ __('Open the diary') }}</x-btn>
        </x-empty>
    @else
        <div class="space-y-5">
            @foreach ($orders as $order)
                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface-2 px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('orders.show', $order) }}" class="text-sm font-semibold text-text hover:text-link">
                                    {{ $order->customer_name }}
                                </a>
                                <x-badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-badge>
                            </div>
                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ $order->event_date->format('D j M') }}
                                @if ($order->serve_time) · {{ __('serving :time', ['time' => \Illuminate\Support\Str::substr($order->serve_time, 0, 5)]) }} @endif
                                · {{ trans_choice('{1}1 guest|[2,*]:count guests', $order->guests, ['count' => $order->guests]) }}
                                @if ($order->venue) · {{ $order->venue }} @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-surface-3">
                                <div class="h-full rounded-full bg-good transition-all" style="width: {{ $order->taskProgress() }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-text tabular-nums">{{ $order->taskProgress() }}%</span>
                        </div>
                    </div>

                    @if ($order->tasks->isEmpty())
                        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                            <p class="text-sm text-text-muted">{{ __('No checklist on this job yet.') }}</p>
                            <form method="POST" action="{{ route('orders.tasks.standard', $order) }}">
                                @csrf
                                <x-btn type="submit" size="sm" variant="secondary">{{ __('Add the standard checklist') }}</x-btn>
                            </form>
                        </div>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($order->tasks as $task)
                                <li class="flex items-center gap-3 px-5 py-3 {{ $task->is_done ? 'opacity-55' : '' }}">
                                    <form method="POST" action="{{ route('prep.toggle', $task) }}" class="shrink-0">
                                        @csrf
                                        <button type="submit"
                                                aria-label="{{ $task->is_done ? __('Untick :task', ['task' => $task->title]) : __('Tick off :task', ['task' => $task->title]) }}"
                                                class="tap grid place-items-center rounded-lg border transition-colors
                                                       {{ $task->is_done ? 'border-good bg-good text-surface' : 'border-line-strong text-text-faint hover:border-good hover:text-good' }}">
                                            <x-icon name="check" class="size-4" />
                                        </button>
                                    </form>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-text {{ $task->is_done ? 'line-through' : '' }}">
                                            {{ $task->title }}
                                        </span>
                                        <span class="mt-0.5 block truncate text-xs text-text-muted">
                                            {{ $task->stageLabel() }}
                                            @if ($task->assignee) · {{ $task->assignee->full_name }} @endif
                                            @if ($task->dueAt()) · {{ $task->dueAt()->format('D H:i') }} @endif
                                        </span>
                                    </span>

                                    @if ($task->isOverdue())
                                        <x-badge tone="bad">{{ __('Overdue') }}</x-badge>
                                    @endif

                                    <form method="POST" action="{{ route('prep.destroy', $task) }}" class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <x-btn type="submit" size="sm" variant="ghost" :confirm="__('Remove this task?')">
                                            <x-icon name="trash" class="size-3.5 text-bad" />
                                            <span class="sr-only">{{ __('Remove') }}</span>
                                        </x-btn>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
@endsection
