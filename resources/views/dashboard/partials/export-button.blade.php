{{--
    Excel in and out for one page, dropped in with a single include:
    @include('dashboard.partials.export-button', ['sheet' => 'staff', 'import' => true])

    sheet: staff | stock | shifts | orders. Management and the owner only —
    the sheets carry pay rates and stock costs.
--}}
@php
    $import ??= false;
    $allowed = auth()->user()?->canSeeFinancials() ?? false;

    // Dated sheets get a From/To picker; the rest are a single button.
    $ranged = [
        'shifts' => [route('excel.shifts.export'), now()->startOfWeek(\Carbon\Carbon::MONDAY), __('Export shifts to Excel')],
        'orders' => [route('reports.export', 'jobs'), now()->startOfMonth(), __('Export orders to Excel')],
        'wages' => [route('excel.wages.export'), now()->startOfMonth(), __('Export wages paid to Excel')],
    ];
@endphp

@if ($allowed)
    @if (isset($ranged[$sheet]))
        @php [$action, $start, $buttonLabel] = $ranged[$sheet]; @endphp
        <form method="GET" action="{{ $action }}" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
            <x-field name="from" type="date" :label="__('Export from')"
                     :value="$start->toDateString()" />
            <x-field name="to" type="date" :label="__('Export to')"
                     :value="($sheet === 'shifts' ? $start->copy()->addDays(6) : $start->copy()->endOfMonth())->toDateString()" />
            <x-btn variant="secondary" type="submit" icon="download">{{ $buttonLabel }}</x-btn>
            <p class="text-xs text-text-faint">{{ __('Opens in Excel.') }}</p>
        </form>

        @if ($import)
            <div class="mb-5">
                @include('dashboard.partials.import-card', ['sheet' => $sheet])
            </div>
        @endif
    @else
        <div class="mb-5 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-btn variant="secondary" size="sm" icon="download" :href="route('excel.'.$sheet.'.export')">
                    {{ __('Export to Excel') }}
                </x-btn>
            </div>

            @if ($import)
                @include('dashboard.partials.import-card', ['sheet' => $sheet])
            @endif
        </div>
    @endif
@endif
