@extends('layouts.app')

@section('title', __('Weekly pay run'))
@section('subtitle', $week->format('j M') . ' – ' . $week->addDays(6)->format('j M Y'))

@section('content')
    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field name="week" type="date" :label="__('Any day in the week')" :value="$week->toDateString()" />
        <x-btn variant="secondary" type="submit">{{ __('Show') }}</x-btn>
        <x-btn variant="ghost" :href="route('timesheets', ['week' => $week->subDays(7)->toDateString()])">{{ __('Previous week') }}</x-btn>
        <x-btn variant="ghost" :href="route('timesheets', ['week' => $week->addDays(7)->toDateString()])">{{ __('Next week') }}</x-btn>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-4">
        <div class="card p-5">
            <p class="label-sm">{{ __('People') }}</p>
            <p class="mt-2 text-3xl font-semibold text-text tabular-nums">{{ $totals['people'] }}</p>
        </div>
        <div class="card p-5">
            <p class="label-sm">{{ __('Hours') }}</p>
            <p class="mt-2 text-3xl font-semibold text-text tabular-nums">{{ number_format($totals['hours'], 2) }}</p>
        </div>
        @if (auth()->user()->canSeeFinancials())
            <div class="card p-5">
                <p class="label-sm">{{ __('Wage bill') }}</p>
                <p class="mt-2 text-3xl font-semibold text-text tabular-nums">£{{ number_format($totals['pay'], 2) }}</p>
            </div>
        @endif
        <div class="card p-5">
            <p class="label-sm">{{ __('Absences') }}</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums {{ $totals['absences'] ? 'text-bad' : 'text-text' }}">{{ $totals['absences'] }}</p>
        </div>
    </div>

    @if ($unapproved > 0)
        <form method="POST" action="{{ route('timesheets.approve') }}" class="card mb-5 flex flex-wrap items-center justify-between gap-3 border-gold/40 bg-gold/8 p-4">
            @csrf
            <input type="hidden" name="week" value="{{ $week->toDateString() }}">
            <div>
                <p class="text-sm font-semibold text-text">
                    {{ trans_choice('{1}1 shift waiting to be approved|[2,*]:count shifts waiting to be approved', $unapproved, ['count' => $unapproved]) }}
                </p>
                <p class="text-xs text-text-muted">{{ __('Approving locks in the rates for this week so a later pay rise cannot change it.') }}</p>
            </div>
            <x-btn type="submit">{{ __('Approve the week') }}</x-btn>
        </form>
    @endif

    @if ($payRun->isEmpty())
        <x-empty :title="__('Nothing recorded for this week')" :body="__('Roster people onto shifts and clock them in and out; the week builds itself from there.')">
            <x-btn :href="route('attendance')">{{ __('Go to attendance') }}</x-btn>
        </x-empty>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[44rem] text-sm">
                <thead class="border-b border-line bg-surface-2 text-start">
                    <tr class="label-sm">
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Hours') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Overtime') }}</th>
                        @if (auth()->user()->canSeeFinancials())
                            <th class="px-4 py-3 text-end">{{ __('Normal pay') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Overtime pay') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Total') }}</th>
                        @endif
                        <th class="px-4 py-3 text-end">{{ __('Absent') }}</th>
                        @if (auth()->user()->canSeeFinancials())
                            <th class="px-4 py-3 text-end">{{ __('Paid') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($payRun as $row)
                        <tr class="hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-text">{{ $row['staff']->full_name }}</td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row['normal_hours'], 2) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums {{ $row['overtime_hours'] > 0 ? 'font-semibold text-warn' : 'text-text-faint' }}">
                                {{ number_format($row['overtime_hours'], 2) }}
                            </td>
                            @if (auth()->user()->canSeeFinancials())
                                <td class="px-4 py-3 text-end tabular-nums">£{{ number_format($row['normal_pay'], 2) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">£{{ number_format($row['overtime_pay'], 2) }}</td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums">£{{ number_format($row['total_pay'], 2) }}</td>
                            @endif
                            <td class="px-4 py-3 text-end tabular-nums {{ $row['absences'] ? 'font-semibold text-bad' : 'text-text-faint' }}">
                                {{ $row['absences'] }}
                            </td>
                            @if (auth()->user()->canSeeFinancials())
                                @php $paid = $payments->get($row['staff']->id, collect()); @endphp
                                <td class="px-4 py-3 text-end">
                                    @foreach ($paid as $payment)
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <x-badge tone="good">{{ money($payment->amount) }} · {{ $payment->methodLabel() }} · {{ $payment->paid_on->format('j M') }}</x-badge>
                                            <form method="POST" action="{{ route('wage-payments.destroy', $payment) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-text-faint hover:text-bad" data-confirm="{{ __('Remove this payment?') }}" aria-label="{{ __('Remove this payment') }}">✕</button>
                                            </form>
                                        </div>
                                    @endforeach
                                    @if ($paid->isEmpty())
                                        <form method="POST" action="{{ route('timesheets.pay') }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            <input type="hidden" name="staff_profile_id" value="{{ $row['staff']->id }}">
                                            <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                                            <label class="sr-only" for="pay-{{ $row['staff']->id }}">{{ __('Amount') }}</label>
                                            <input id="pay-{{ $row['staff']->id }}" name="amount" type="number" step="0.01" min="0.01" required
                                                   value="{{ number_format($row['total_pay'], 2, '.', '') }}"
                                                   class="tap w-24 rounded-lg border border-line-strong bg-surface px-2 py-1 text-end text-sm text-text tabular-nums">
                                            <x-btn type="submit" size="sm">{{ __('Paid in cash') }}</x-btn>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-text-faint">
            {{ __('Overtime applies past :n hours in a week.', ['n' => $overtimeAfter]) }}
        </p>
    @endif
@endsection
