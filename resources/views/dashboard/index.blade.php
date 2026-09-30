@extends('layouts.app')

@section('title', __('Overview'))
@section('subtitle', __('Welcome back, :name', ['name' => auth()->user()->name]))

@section('content')
    {{-- Monday and Tuesday: the notebook catch-up and last week's summary. --}}
    @if ($can['money'] && (today()->isMonday() || today()->isTuesday()))
        @php $lastWeek = today()->subWeek()->startOfWeek(); @endphp
        <section class="card mb-6 flex flex-wrap items-center justify-between gap-4 border-gold/40 bg-gold/8 p-5">
            <div>
                <p class="text-sm font-semibold text-text">{{ __('Time to enter last week\'s orders') }}</p>
                <p class="text-xs text-text-muted">{{ __('Week of :date — type them in from the notebook, then check the summary.', ['date' => $lastWeek->format('j M')]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-btn size="sm" icon="plus" :href="route('order-book.create', ['date' => $lastWeek->toDateString()])">{{ __('Enter orders') }}</x-btn>
                <x-btn size="sm" variant="secondary" :href="route('weekly-summary', ['week' => $lastWeek->toDateString()])">{{ __('Last week\'s summary') }}</x-btn>
            </div>
        </section>
    @endif
    {{-- ------------------------------------------------- what needs doing --}}
    @if ($alerts->isNotEmpty())
        <section id="alerts" class="mb-6">
            <h2 class="label-sm mb-3">{{ __('Needs attention') }}</h2>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($alerts as $alert)
                    @php
                        $ring = ['bad' => 'border-bad/35', 'warn' => 'border-warn/35', 'info' => 'border-info/35'][$alert['tone']] ?? 'border-line';
                        $ink = ['bad' => 'text-bad', 'warn' => 'text-warn', 'info' => 'text-info'][$alert['tone']] ?? 'text-text';
                    @endphp

                    <a href="{{ $alert['href'] }}" class="card flex items-start gap-3 border p-4 transition-colors hover:bg-surface-2 {{ $ring }}">
                        <span class="mt-0.5 shrink-0 {{ $ink }}">
                            <x-icon :name="$alert['icon']" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-text">{{ $alert['title'] }}</span>
                            <span class="mt-0.5 block text-xs {{ $ink }}">{{ $alert['detail'] }}</span>
                        </span>
                        <x-icon name="arrow-right" class="mt-1 size-4 shrink-0 text-text-faint rtl:rotate-180" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- --------------------------------------------------------- headline --}}
    {{-- Each card only links where this person can go; cards about areas they
         cannot open are left out rather than shown as a dead end. --}}
    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Jobs today')" :value="$counts['today_jobs']" icon="calendar"
                :hint="trans_choice('{0}Nothing on|{1}1 guest|[2,*]:count guests', $counts['today_guests'], ['count' => number_format($counts['today_guests'])])"
                :href="$can['jobs'] ? route('calendar') : null" />

        <x-stat :label="__('Next 14 days')" :value="$counts['upcoming_jobs']" icon="clipboard"
                :hint="__('Jobs coming up')" :href="$can['jobs'] ? route('orders', ['view' => 'upcoming']) : null" />

        @if ($can['people'])
            <x-stat :label="__('On site now')" :value="$counts['on_site']" icon="clock"
                    :tone="$counts['on_site'] > 0 ? 'good' : 'neutral'"
                    :hint="__('Clocked in')" :href="route('attendance')" />
        @elseif ($hasTimesheet)
            <x-stat :label="__('My timesheet')" :value="__('Open')" icon="clock"
                    :hint="__('Your shifts and hours this week')" :href="route('my-timesheet')" />
        @endif

        @if ($can['quotes'])
            <x-stat :label="__('Open quotes')" :value="$counts['open_quotes']" icon="file-text"
                    :hint="__('Draft or awaiting a reply')" :href="route('quotes')" />
        @endif
    </section>

    {{-- ------------------------------------------------------------ money --}}
    @if ($money)
        <section class="mb-6">
            <h2 class="label-sm mb-3">{{ __('This month') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat :label="__('Booked')" :value="'£'.number_format($money['revenue'] / 100, 2)" icon="pound"
                        :hint="__('Value of jobs this month')" :href="route('reports')" />

                <x-stat :label="__('Collected')" :value="'£'.number_format($money['collected'] / 100, 2)" tone="good"
                        :hint="__('Money actually in')" />

                <x-stat :label="__('Outstanding')" :value="'£'.number_format($money['outstanding'] / 100, 2)"
                        :tone="$money['outstanding'] > 0 ? 'warn' : 'neutral'"
                        :hint="__('Still to collect')" :href="route('orders', ['view' => 'unpaid'])" />

                <x-stat :label="__('Costs')" :value="'£'.number_format(($money['expenses'] + $money['waste']) / 100, 2)"
                        :hint="__('Expenses and waste')" :href="route('expenses')" />
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- ------------------------------------------------------- today --}}
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="label-sm">{{ __('Today') }}</h2>
                @if ($can['jobs'])
                    <a href="{{ route('calendar') }}" class="-my-2 inline-flex min-h-[36px] items-center px-1 text-xs font-semibold text-link hover:underline">{{ __('Open the diary') }}</a>
                @endif
            </div>

            @if ($today->isEmpty())
                <x-empty icon="calendar" :title="__('Nothing on today')"
                         :body="__('A quiet day. The next jobs are listed below.')" />
            @else
                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($today as $order)
                        <{{ $can['jobs'] ? 'a' : 'div' }} @if ($can['jobs']) href="{{ route('orders.show', $order) }}" @endif class="tap flex items-center gap-3 p-4 transition-colors {{ $can['jobs'] ? 'hover:bg-surface-2' : '' }}">
                            <span class="w-14 shrink-0 text-center">
                                <span class="block text-sm font-bold text-text tabular-nums">
                                    {{ $order->serve_time ? \Illuminate\Support\Str::substr($order->serve_time, 0, 5) : '—' }}
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-text">{{ $order->customer_name }}</span>
                                    <x-badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-badge>
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-text-muted">
                                    {{ trans_choice('{0}No guest count|{1}1 guest|[2,*]:count guests', $order->guests, ['count' => $order->guests]) }}
                                    @if ($order->venue) · {{ $order->venue }} @endif
                                </span>
                            </span>

                            @if ($order->tasks->isNotEmpty())
                                <span class="shrink-0 text-end">
                                    <span class="block text-xs font-semibold text-text tabular-nums">{{ $order->taskProgress() }}%</span>
                                    <span class="block text-[0.68rem] text-text-faint">{{ __('prep') }}</span>
                                </span>
                            @endif
                        </{{ $can['jobs'] ? 'a' : 'div' }}>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- --------------------------------------------------- next tasks --}}
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="label-sm">{{ __('Next up in the kitchen') }}</h2>
                @if ($can['kitchen'])
                    <a href="{{ route('prep') }}" class="-my-2 inline-flex min-h-[36px] items-center px-1 text-xs font-semibold text-link hover:underline">{{ __('Prep board') }}</a>
                @endif
            </div>

            @if ($nextTasks->isEmpty())
                <x-empty icon="check-circle" :title="__('Nothing outstanding')"
                         :body="__('No prep tasks due in the next few days.')" />
            @else
                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($nextTasks as $task)
                        <div class="flex items-center gap-3 p-4">
                            @if ($can['kitchen'])
                                <form method="POST" action="{{ route('prep.toggle', $task) }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" aria-label="{{ __('Tick off :task', ['task' => $task->title]) }}"
                                            class="tap grid place-items-center rounded-lg border border-line-strong text-text-faint transition-colors hover:border-good hover:text-good">
                                        <x-icon name="check" class="size-4" />
                                    </button>
                                </form>
                            @endif

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-text">{{ $task->title }}</span>
                                <span class="mt-0.5 block truncate text-xs text-text-muted">
                                    {{ $task->order->customer_name }}
                                    @if ($task->assignee) · {{ $task->assignee->full_name }} @endif
                                </span>
                            </span>

                            @if ($task->isOverdue())
                                <x-badge tone="bad">{{ __('Overdue') }}</x-badge>
                            @elseif ($task->dueAt())
                                <span class="shrink-0 text-xs text-text-faint">{{ $task->dueAt()->diffForHumans(['short' => true]) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ------------------------------------------------------ upcoming --}}
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="label-sm">{{ __('Coming up') }}</h2>
                @if ($can['jobs'])
                    <a href="{{ route('orders') }}" class="-my-2 inline-flex min-h-[36px] items-center px-1 text-xs font-semibold text-link hover:underline">{{ __('All jobs') }}</a>
                @endif
            </div>

            @if ($upcoming->isEmpty())
                <x-empty icon="clipboard" :title="__('No jobs booked')"
                         :body="__('Once a quote is accepted the job appears here.')">
                    @if ($can['quotes'])
                        <x-btn size="sm" :href="route('quotes.create')" icon="plus">{{ __('Raise a quote') }}</x-btn>
                    @endif
                </x-empty>
            @else
                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($upcoming as $order)
                        <{{ $can['jobs'] ? 'a' : 'div' }} @if ($can['jobs']) href="{{ route('orders.show', $order) }}" @endif class="tap flex items-center gap-3 p-4 transition-colors {{ $can['jobs'] ? 'hover:bg-surface-2' : '' }}">
                            <span class="w-14 shrink-0 text-center">
                                <span class="block text-[0.68rem] font-semibold text-text-faint uppercase">{{ $order->event_date->format('M') }}</span>
                                <span class="block text-base font-bold text-text tabular-nums">{{ $order->event_date->format('j') }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-text">{{ $order->customer_name }}</span>
                                <span class="mt-0.5 block truncate text-xs text-text-muted">
                                    {{ $order->event_date->diffForHumans() }}
                                    @if ($order->venue) · {{ $order->venue }} @endif
                                </span>
                            </span>
                            @if ($can['money'] && $order->balanceAmount() > 0)
                                <x-money :pence="$order->balanceAmount()" tone="warn" class="shrink-0 text-xs font-semibold" />
                            @endif
                        </{{ $can['jobs'] ? 'a' : 'div' }}>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ------------------------------------------------------- unpaid --}}
        @if (auth()->user()->canSeeFinancials())
            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="label-sm">{{ __('Waiting on payment') }}</h2>
                    <a href="{{ route('orders', ['view' => 'unpaid']) }}" class="-my-2 inline-flex min-h-[36px] items-center px-1 text-xs font-semibold text-link hover:underline">{{ __('See all') }}</a>
                </div>

                @if ($unpaid->isEmpty())
                    <x-empty icon="check-circle" :title="__('Everything is settled')"
                             :body="__('No job is carrying an unpaid balance.')" />
                @else
                    <div class="card divide-y divide-line overflow-hidden">
                        @foreach ($unpaid as $order)
                            <a href="{{ route('orders.show', $order) }}" class="tap flex items-center justify-between gap-3 p-4 transition-colors hover:bg-surface-2">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-text">{{ $order->customer_name }}</span>
                                    <span class="mt-0.5 block text-xs text-text-muted">
                                        {{ $order->event_date->format('j M Y') }}
                                        @if ($order->event_date->isPast()) · <span class="font-semibold text-bad">{{ __('overdue') }}</span> @endif
                                    </span>
                                </span>
                                <x-money :pence="$order->balanceAmount()" :tone="$order->event_date->isPast() ? 'bad' : 'warn'" class="shrink-0 text-sm font-semibold" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
