@extends('layouts.app')

@section('title', __('Staff'))

@section('content')
    <x-page-head :title="__('Staff')" :subtitle="__('People, rates and holiday')" />

    @if ($pendingLeave->isNotEmpty())
        <div class="card mb-5 overflow-hidden border-warn/35">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-warn">
                    {{ trans_choice('{1}1 leave request waiting|[2,*]:count leave requests waiting', $pendingLeave->count(), ['count' => $pendingLeave->count()]) }}
                </h2>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($pendingLeave as $leave)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-text">{{ $leave->staff->full_name }}</span>
                            <span class="block text-xs text-text-muted">
                                {{ $leave->typeLabel() }} ·
                                {{ $leave->starts_on->format('j M') }} – {{ $leave->ends_on->format('j M Y') }} ·
                                {{ qty($leave->hours, 1) }} {{ __('hrs') }}
                            </span>
                        </span>
                        <span class="flex gap-2">
                            <form method="POST" action="{{ route('leave.decide', $leave) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <x-btn type="submit" size="sm" variant="secondary">{{ __('Approve') }}</x-btn>
                            </form>
                            <form method="POST" action="{{ route('leave.decide', $leave) }}">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <x-btn type="submit" size="sm" variant="ghost">{{ __('Reject') }}</x-btn>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Active staff')" :value="$counts['total']" icon="users" />
        <x-stat :label="__('Full time')" :value="$counts['full_time']" />
        <x-stat :label="__('Event staff')" :value="$counts['event']" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div>
            <x-filters :reset="route('staff')">
                <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[11rem] flex-1" />
                <x-field name="department" type="select" :label="__('Department')" :value="request('department')" class="min-w-[9rem]"
                         :options="['' => __('All'), 'kitchen' => __('Kitchen'), 'service' => __('Service'), 'delivery' => __('Delivery'), 'management' => __('Management')]" />
            </x-filters>

            <x-tabs param="filter" :current="$filter" :tabs="['' => __('Active'), 'archived' => __('Archived')]" />

            @if ($staff->isEmpty())
                <x-empty icon="users" :title="__('Nobody here')" :body="__('Add your team so shifts and pay can be tracked.')" />
            @else
                <x-table :head="[__('Name'), __('Department'), __('Type'), __('Rate'), __('Holiday left'), '']"
                         :align="['start', 'start', 'start', 'end', 'end', 'end']">
                    @foreach ($staff as $person)
                        <tr data-row-href="{{ route('staff.show', $person) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <a href="{{ route('staff.show', $person) }}" class="font-medium text-text hover:text-link">{{ $person->full_name }}</a>
                                @if ($person->phone)<span class="block text-xs text-text-faint tabular-nums">{{ $person->phone }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-text-muted">{{ __(ucfirst($person->department)) }}</td>
                            <td class="px-4 py-3"><x-badge>{{ __(ucfirst(str_replace('_', ' ', $person->employment_type))) }}</x-badge></td>
                            <td class="px-4 py-3 text-end tabular-nums text-text-muted">
                                {{ $person->hourly_rate ? '£'.number_format((float) $person->hourly_rate, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-text-muted">{{ qty($person->holidayHoursRemaining(), 1) }}</td>
                            <td class="px-4 py-3 text-end">
                                <x-icon name="arrow-right" class="ms-auto size-4 text-text-faint rtl:rotate-180" />
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="mt-5">{{ $staff->links() }}</div>
            @endif
        </div>

        <form method="POST" action="{{ route('staff.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            <h2 class="text-sm font-semibold text-text">{{ __('Add someone') }}</h2>
            <x-field name="full_name" :label="__('Full name')" required />
            <x-field name="phone" type="tel" :label="__('Phone')" />
            <x-field name="department" type="select" :label="__('Department')" required
                     :options="['kitchen' => __('Kitchen'), 'service' => __('Service'), 'delivery' => __('Delivery'), 'management' => __('Management')]" />
            <x-field name="employment_type" type="select" :label="__('Type')" required
                     :options="['full_time' => __('Full time'), 'part_time' => __('Part time'), 'event_staff' => __('Event staff')]" />
            <x-field name="hourly_rate" type="number" step="0.01" min="0" prefix="£" :label="__('Hourly rate')" />
            <x-field name="overtime_rate" type="number" step="0.01" min="0" prefix="£" :label="__('Overtime rate')" />
            <x-field name="holiday_allowance_hours" type="number" min="0" suffix="hrs" :label="__('Holiday allowance')" :value="0" />
            <x-field name="started_on" type="date" :label="__('Started')" />
            <x-btn type="submit" class="w-full">{{ __('Add') }}</x-btn>
        </form>
    </div>
@endsection
