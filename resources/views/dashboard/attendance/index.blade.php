@extends('layouts.app')

@section('title', __('Attendance'))
@section('subtitle', $day->format('l j F Y'))

@section('content')
    @include('dashboard.partials.export-button', ['sheet' => 'shifts'])
    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field name="day" type="date" :label="__('Day')" :value="$day->toDateString()" />
        <x-btn variant="secondary" type="submit">{{ __('Show') }}</x-btn>
        <x-btn variant="ghost" :href="route('timesheets')">{{ __('Weekly pay run') }}</x-btn>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-4">
        @foreach ([
            [__('Rostered'), $records->count(), 'text-text'],
            [__('On site now'), $onSite, 'text-good'],
            [__('Late'), $late, 'text-warn'],
            [__('Absent'), $absent, 'text-bad'],
        ] as [$label, $value, $tone])
            <div class="card p-5">
                <p class="label-sm">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- The simple way: everyone's hours for the day in one grid, one Save. --}}
    @if (! $day->isFuture())
        <details class="card mb-5 overflow-hidden" open>
            <summary class="tap flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                <span>
                    <span class="block text-sm font-semibold text-text">{{ __('Quick shifts for :day', ['day' => $day->format('l j M')]) }}</span>
                    <span class="block text-xs text-text-muted">{{ __('Type start and finish for whoever worked, leave the rest blank, then Save. A finish after midnight is fine.') }}</span>
                </span>
                <x-icon name="clock" class="size-4 shrink-0 text-text-faint" />
            </summary>

            <form method="POST" action="{{ route('attendance.quick') }}" class="border-t border-line">
                @csrf
                <input type="hidden" name="worked_on" value="{{ $day->toDateString() }}">
                <div class="table-scroll">
                    <table class="w-full min-w-[34rem] text-sm">
                        <thead class="border-b border-line bg-surface-2">
                            <tr class="label-sm">
                                <th class="px-4 py-2.5 text-start">{{ __('Name') }}</th>
                                <th class="px-2 py-2.5 text-start">{{ __('Start') }}</th>
                                <th class="px-2 py-2.5 text-start">{{ __('Finish') }}</th>
                                <th class="px-2 py-2.5 text-start">{{ __('Break (min)') }}</th>
                                <th class="px-4 py-2.5 text-start">{{ __('Already recorded') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($staff as $person)
                                @php $existing = $records->where('staff_profile_id', $person->id); @endphp
                                <tr>
                                    <td class="px-4 py-2 font-medium text-text">{{ $person->full_name }}</td>
                                    <td class="px-2 py-2">
                                        <input type="time" name="shifts[{{ $person->id }}][start]" value="{{ old('shifts.'.$person->id.'.start') }}" aria-label="{{ __('Start for :name', ['name' => $person->full_name]) }}"
                                               class="tap w-28 rounded-lg border border-line-strong bg-surface px-2 py-1 text-sm text-text tabular-nums">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="time" name="shifts[{{ $person->id }}][end]" value="{{ old('shifts.'.$person->id.'.end') }}" aria-label="{{ __('Finish for :name', ['name' => $person->full_name]) }}"
                                               class="tap w-28 rounded-lg border border-line-strong bg-surface px-2 py-1 text-sm text-text tabular-nums">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" min="0" max="480" name="shifts[{{ $person->id }}][break]" value="{{ old('shifts.'.$person->id.'.break') }}" placeholder="0" aria-label="{{ __('Break for :name', ['name' => $person->full_name]) }}"
                                               class="tap w-20 rounded-lg border border-line-strong bg-surface px-2 py-1 text-sm text-text tabular-nums">
                                    </td>
                                    <td class="px-4 py-2 text-xs text-text-muted">
                                        @foreach ($existing as $shift)
                                            <span class="block">
                                                {{ $shift->clock_in_at?->format('H:i') ?? '—' }}–{{ $shift->clock_out_at?->format('H:i') ?? '…' }}
                                                @if ($shift->clock_out_at) · {{ number_format($shift->paidHours(), 2) }} {{ __('hrs') }} @endif
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-line bg-surface-2 px-5 py-3">
                    <x-btn type="submit">{{ __('Save shifts') }}</x-btn>
                </div>
            </form>
        </details>
    @endif

    @if ($stillOpen->isNotEmpty() && ! $day->isToday())
        <div class="card mb-5 border-warn/30 bg-warn-bg p-4">
            <p class="text-sm font-semibold text-warn">
                {{ trans_choice('{1}1 person is still clocked in|[2,*]:count people are still clocked in', $stillOpen->count(), ['count' => $stillOpen->count()]) }}
            </p>
            <p class="mt-1 text-xs text-warn">{{ $stillOpen->map(fn ($r) => $r->staff->full_name)->join(', ') }}</p>
        </div>
    @endif

    @error('clock')
        <div class="card mb-5 border-bad/30 bg-bad-bg p-4 text-sm text-bad">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold text-text">{{ __('The day') }}</h2>
            </div>

            @if ($records->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-text-muted">{{ __('Nobody is rostered for this day yet.') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($records as $record)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-text">{{ $record->staff->full_name }}</span>
                                    <x-badge :tone="match ($record->status) {
                                        'approved', 'closed' => 'good',
                                        'open' => 'info',
                                        'absent' => 'bad',
                                        default => 'neutral',
                                    }">{{ $record->statusLabel() }}</x-badge>
                                    @if ($record->isLate())
                                        <x-badge tone="warn">{{ __(':n min late', ['n' => $record->minutesLate()]) }}</x-badge>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-text-muted">
                                    @if ($record->order)
                                        {{ $record->order->venue ?: $record->order->customer_name }} ·
                                    @endif
                                    @if ($record->scheduled_start_at)
                                        {{ __('due :time', ['time' => $record->scheduled_start_at->format('H:i')]) }}
                                    @endif
                                    @if ($record->clock_in_at)
                                        · {{ __('in :time', ['time' => $record->clock_in_at->format('H:i')]) }}
                                    @endif
                                    @if ($record->clock_out_at)
                                        · {{ __('out :time', ['time' => $record->clock_out_at->format('H:i')]) }}
                                        · {{ __(':n hrs', ['n' => number_format($record->paidHours(), 2)]) }}
                                    @endif
                                    · {{ $record->methodLabel() }}
                                </span>
                            </span>

                            <span class="flex shrink-0 gap-2">
                                @if ($record->clock_in_at === null && $record->status !== 'absent')
                                    <form method="POST" action="{{ route('attendance.clock-in', $record) }}">
                                        @csrf
                                        <x-btn type="submit" variant="secondary">{{ __('Clock in') }}</x-btn>
                                    </form>
                                    <form method="POST" action="{{ route('attendance.absent', $record) }}">
                                        @csrf
                                        <x-btn type="submit" variant="ghost">{{ __('Absent') }}</x-btn>
                                    </form>
                                @elseif ($record->isOpen())
                                    <form method="POST" action="{{ route('attendance.clock-out', $record) }}" class="flex items-end gap-2">
                                        @csrf
                                        <span class="w-20">
                                            <label class="block text-[0.68rem] font-semibold text-text-muted" for="brk-{{ $record->id }}">{{ __('Break') }}</label>
                                            <input id="brk-{{ $record->id }}" type="number" min="0" max="480" name="break_minutes" value="{{ $record->break_minutes }}"
                                                   class="tap mt-1 w-full rounded-lg border border-line-strong px-2 py-2 text-end text-sm tabular-nums outline-none focus:border-gold">
                                        </span>
                                        <x-btn type="submit">{{ __('Clock out') }}</x-btn>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="space-y-4">
            {{-- Roster people onto a job. This is what a supervisor opens at a venue. --}}
            <form method="POST" action="{{ route('attendance.roster') }}" class="card space-y-4 p-5">
                @csrf
                <h2 class="text-sm font-semibold text-text">{{ __('Roster staff') }}</h2>
                <p class="text-xs text-text-muted">{{ __('Put people on a shift so lateness and absence have something to measure against.') }}</p>

                <x-field name="worked_on" type="date" :label="__('Day')" :value="$day->toDateString()" required />
                <x-field name="order_id" type="select" :label="__('Job')"
                         :options="['' => __('Kitchen — no job')] + $orders->mapWithKeys(fn ($o) => [$o->id => ($o->venue ?: $o->customer_name)])->all()" />
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="scheduled_start" type="time" :label="__('Start')" value="09:00" required />
                    <x-field name="scheduled_end" type="time" :label="__('End')" value="17:00" />
                </div>

                <fieldset>
                    <legend class="block text-xs font-semibold text-text-muted">{{ __('Who') }}</legend>
                    <div class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-line p-2">
                        @foreach ($staff as $person)
                            <label class="tap flex cursor-pointer items-center gap-2.5 rounded px-2 text-sm text-text hover:bg-surface-2">
                                <input type="checkbox" name="staff_profile_ids[]" value="{{ $person->id }}"
                                       class="size-4 rounded border-line-strong text-gold focus:ring-gold/40">
                                <span class="min-w-0 flex-1 truncate">{{ $person->full_name }}</span>
                                <span class="shrink-0 text-xs text-text-faint">{{ __(ucfirst(str_replace('_', ' ', $person->department))) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('staff_profile_ids')
                        <p class="mt-1 text-xs font-medium text-bad">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-btn type="submit">{{ __('Add to the roster') }}</x-btn>
            </form>

            {{-- Somebody forgot to clock --}}
            <details class="card p-5">
                <summary class="tap cursor-pointer list-none text-sm font-semibold text-text">{{ __('Enter a shift by hand') }}</summary>
                <form method="POST" action="{{ route('attendance.manual') }}" class="mt-4 space-y-4 border-t border-line pt-4">
                    @csrf
                    <x-field name="staff_profile_id" type="select" :label="__('Who')" required
                             :options="$staff->pluck('full_name', 'id')->all()" />
                    <x-field name="worked_on" type="date" :label="__('Day')" :value="$day->toDateString()" required />
                    <div class="grid grid-cols-2 gap-3">
                        <x-field name="clock_in" type="time" :label="__('In')" required />
                        <x-field name="clock_out" type="time" :label="__('Out')" required />
                    </div>
                    <x-field name="break_minutes" type="number" min="0" :label="__('Break (minutes)')" :value="0" />
                    <x-field name="notes" :label="__('Why it is being added late')" />
                    <x-btn type="submit" variant="secondary">{{ __('Add the shift') }}</x-btn>
                </form>
            </details>
        </div>
    </div>
@endsection
