@extends('layouts.app')

@section('title', __('My timesheet'))

@section('content')
    <x-page-head :title="__('My timesheet')"
                 :subtitle="__('Week of :date', ['date' => $weekStart->format('j M Y')])" />

    @if (! $staff)
        <x-empty icon="clock" :title="__('Your login is not linked to the rota yet')"
                 :body="__('Ask management to link your login to your staff record on the Logins page. Your shifts will then show here.')" />
    @else
        <div class="mb-5 flex flex-wrap gap-2">
            <x-btn variant="secondary" size="sm" :href="route('my-timesheet', ['week' => $weekStart->subWeek()->toDateString()])" icon="arrow-left">{{ __('Previous week') }}</x-btn>
            <x-btn variant="secondary" size="sm" :href="route('my-timesheet')">{{ __('This week') }}</x-btn>
            <x-btn variant="secondary" size="sm" :href="route('my-timesheet', ['week' => $weekStart->addWeek()->toDateString()])">{{ __('Next week') }}</x-btn>
        </div>

        <div class="mb-5 grid gap-4 sm:grid-cols-4">
            <x-stat :label="__('Hours worked')" :value="number_format($week['total_hours'], 2)" icon="clock" />
            <x-stat :label="__('Of which overtime')" :value="number_format($week['overtime_hours'], 2)"
                    :tone="$week['overtime_hours'] > 0 ? 'warn' : 'neutral'" />
            <x-stat :label="__('Late starts')" :value="$week['late_shifts']" :tone="$week['late_shifts'] > 0 ? 'warn' : 'neutral'" />
            <x-stat :label="__('Holiday left this year')" :value="qty($staff->holidayHoursRemaining(), 1).' '.__('hrs')"
                    :hint="__('of :n allowed', ['n' => $staff->holiday_allowance_hours])" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Shifts this week') }}</h2>
                </div>
                @if ($week['records']->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('No shifts this week.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($week['records'] as $shift)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-text">{{ $shift->worked_on->format('D j M') }}</span>
                                    <span class="block truncate text-xs text-text-muted">
                                        @if ($shift->scheduled_start_at){{ __('due :time', ['time' => $shift->scheduled_start_at->format('H:i')]) }} · @endif
                                        @if ($shift->clock_in_at){{ __('in :time', ['time' => $shift->clock_in_at->format('H:i')]) }}@endif
                                        @if ($shift->clock_out_at) · {{ __('out :time', ['time' => $shift->clock_out_at->format('H:i')]) }}@endif
                                        @if ($shift->break_minutes) · {{ __(':n min break', ['n' => $shift->break_minutes]) }}@endif
                                        · {{ $shift->order?->venue ?: ($shift->order?->customer_name ?? __('Kitchen')) }}
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    @if ($shift->isLate())<x-badge tone="warn">{{ __(':n min late', ['n' => $shift->minutesLate()]) }}</x-badge>@endif
                                    <x-badge :tone="match ($shift->status) { 'approved', 'closed' => 'good', 'open' => 'info', 'absent' => 'bad', default => 'neutral' }">{{ $shift->statusLabel() }}</x-badge>
                                    @if ($shift->clock_out_at)
                                        <span class="text-sm font-semibold text-text tabular-nums">{{ number_format($shift->paidHours(), 2) }} {{ __('hrs') }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('My leave') }}</h2>
                </div>
                @if ($leave->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('No leave booked. Ask management to book it for you.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($leave as $request)
                            <li class="px-5 py-3">
                                <span class="block text-sm font-medium text-text">{{ $request->typeLabel() }} · {{ qty($request->hours, 1) }} {{ __('hrs') }}</span>
                                <span class="block text-xs text-text-muted">{{ $request->starts_on->format('j M') }} – {{ $request->ends_on->format('j M Y') }} · {{ __(ucfirst($request->status)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
@endsection
