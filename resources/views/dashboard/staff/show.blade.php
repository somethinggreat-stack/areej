@extends('layouts.app')

@section('title', $staff->full_name)

@section('content')
    <x-page-head :title="$staff->full_name" :back="route('staff')" :backLabel="__('Staff')"
                 :subtitle="__(ucfirst($staff->department)).' · '.__(ucfirst(str_replace('_', ' ', $staff->employment_type)))" />

    <div class="mb-5 grid gap-4 sm:grid-cols-4">
        <x-stat :label="__('Hours this week')" :value="number_format($week['total_hours'], 2)" icon="clock" />
        <x-stat :label="__('Overtime')" :value="number_format($week['overtime_hours'], 2)"
                :tone="$week['overtime_hours'] > 0 ? 'warn' : 'neutral'" />
        <x-stat :label="__('Pay this week')" :value="'£'.number_format($week['total_pay'], 2)" />
        <x-stat :label="__('Holiday left')" :value="qty($staff->holidayHoursRemaining(), 1).' '.__('hrs')"
                :hint="__('of :n allowed', ['n' => $staff->holiday_allowance_hours])" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-4">
            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Recent shifts') }}</h2>
                </div>
                @if ($recent->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nothing recorded yet.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($recent as $shift)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-text">{{ $shift->worked_on->format('D j M Y') }}</span>
                                    <span class="block truncate text-xs text-text-muted">
                                        @if ($shift->clock_in_at){{ $shift->clock_in_at->format('H:i') }}@endif
                                        @if ($shift->clock_out_at) – {{ $shift->clock_out_at->format('H:i') }}@endif
                                        @if ($shift->order) · {{ $shift->order->customer_name }} @endif
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    @if ($shift->isLate())<x-badge tone="warn">{{ __(':n min late', ['n' => $shift->minutesLate()]) }}</x-badge>@endif
                                    <x-badge :tone="match ($shift->status) { 'approved', 'closed' => 'good', 'open' => 'info', 'absent' => 'bad', default => 'neutral' }">
                                        {{ $shift->statusLabel() }}
                                    </x-badge>
                                    <span class="text-sm font-semibold text-text tabular-nums">{{ number_format($shift->paidHours(), 2) }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Wages paid') }}</h2>
                    <a href="{{ route('timesheets') }}" class="text-xs font-semibold text-link hover:underline">{{ __('Pay run') }}</a>
                </div>
                @if ($wages->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('No wages recorded yet. Press "Paid in cash" on the pay run.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($wages as $payment)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-text">{{ __('Week of :date', ['date' => $payment->week_start->format('j M Y')]) }}</span>
                                    <span class="block text-xs text-text-muted">{{ $payment->methodLabel() }} · {{ __('paid :date', ['date' => $payment->paid_on->format('j M Y')]) }}</span>
                                </span>
                                <span class="shrink-0 text-sm font-semibold text-text tabular-nums">{{ money($payment->amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Leave') }}</h2>
                </div>
                @if ($leave->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-text-muted">{{ __('No leave recorded.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($leave as $row)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-text">{{ $row->typeLabel() }}</span>
                                    <span class="block text-xs text-text-muted tabular-nums">
                                        {{ $row->starts_on->format('j M') }} – {{ $row->ends_on->format('j M Y') }} · {{ qty($row->hours, 1) }} {{ __('hrs') }}
                                    </span>
                                </span>
                                <x-badge :tone="match ($row->status) { 'approved' => 'good', 'rejected' => 'bad', 'pending' => 'warn', default => 'neutral' }">
                                    {{ __(ucfirst($row->status)) }}
                                </x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('staff.leave.store', $staff) }}" class="grid gap-3 border-t border-line p-5 sm:grid-cols-2">
                    @csrf
                    <x-field name="type" type="select" :label="__('Type')" required
                             :options="\App\Models\LeaveRequest::typeLabels()" />
                    <x-field name="hours" type="number" step="0.5" min="0" suffix="hrs" :label="__('Hours')" required />
                    <x-field name="starts_on" type="date" :label="__('From')" required />
                    <x-field name="ends_on" type="date" :label="__('To')" required />
                    <div class="sm:col-span-2">
                        <x-btn type="submit" variant="secondary">{{ __('Add leave request') }}</x-btn>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            <form method="POST" action="{{ route('staff.update', $staff) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Details') }}</h2>
                <x-field name="full_name" :label="__('Full name')" :value="$staff->full_name" required />
                <x-field name="phone" type="tel" :label="__('Phone')" :value="$staff->phone" />
                <x-field name="department" type="select" :label="__('Department')" required :value="$staff->department"
                         :options="['kitchen' => __('Kitchen'), 'service' => __('Service'), 'delivery' => __('Delivery'), 'management' => __('Management')]" />
                <x-field name="employment_type" type="select" :label="__('Type')" required :value="$staff->employment_type"
                         :options="['full_time' => __('Full time'), 'part_time' => __('Part time'), 'event_staff' => __('Event staff')]" />
                <x-field name="hourly_rate" type="number" step="0.01" min="0" prefix="£" :label="__('Hourly rate')" :value="$staff->hourly_rate" />
                <x-field name="overtime_rate" type="number" step="0.01" min="0" prefix="£" :label="__('Overtime rate')" :value="$staff->overtime_rate" />
                <x-field name="holiday_allowance_hours" type="number" min="0" suffix="hrs" :label="__('Holiday allowance')" :value="$staff->holiday_allowance_hours" />
                <x-field name="started_on" type="date" :label="__('Started')" :value="$staff->started_on?->toDateString()" />
                <x-field name="is_active" type="checkbox" :label="__('Status')" :value="$staff->is_active" :hint="__('Still working here')" />
                <x-btn type="submit">{{ __('Save') }}</x-btn>
            </form>

            <form method="POST" action="{{ route('staff.destroy', $staff) }}" class="card p-5">
                @csrf
                @method('DELETE')
                <x-btn type="submit" variant="danger" class="w-full"
                       :confirm="__('Archive :name? Their worked shifts are kept.', ['name' => $staff->full_name])">
                    {{ __('Archive this person') }}
                </x-btn>
            </form>
        </div>
    </div>
@endsection
