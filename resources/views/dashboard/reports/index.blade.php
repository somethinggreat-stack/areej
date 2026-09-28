@extends('layouts.app')

@section('title', __('Reports'))
@section('subtitle', $from->format('j M Y').' – '.$to->format('j M Y'))

@section('content')
    @php $f = $financials; @endphp

    <x-page-head :title="__('Reports')" :subtitle="$from->format('j M Y').' – '.$to->format('j M Y')">
        <x-btn variant="secondary" size="sm" icon="download"
               :href="route('reports.export', ['report' => 'jobs', 'from' => $from->toDateString(), 'to' => $to->toDateString()])">
            {{ __('Jobs CSV') }}
        </x-btn>
        <x-btn variant="secondary" size="sm" icon="download"
               :href="route('reports.export', ['report' => 'expenses', 'from' => $from->toDateString(), 'to' => $to->toDateString()])">
            {{ __('Expenses CSV') }}
        </x-btn>
        <x-btn variant="secondary" size="sm" icon="download"
               :href="route('reports.export', ['report' => 'stock', 'from' => $from->toDateString(), 'to' => $to->toDateString()])">
            {{ __('Stock CSV') }}
        </x-btn>
    </x-page-head>

    <x-tabs param="preset" :current="$preset" :tabs="[
        'week' => __('This week'),
        'month' => __('This month'),
        'last_month' => __('Last month'),
        'quarter' => __('This quarter'),
        'year' => __('This year'),
    ]" />

    <x-filters :reset="route('reports')">
        <x-field name="from" type="date" :label="__('From')" :value="$from->toDateString()" class="w-40" />
        <x-field name="to" type="date" :label="__('To')" :value="$to->toDateString()" class="w-40" />
    </x-filters>

    {{-- ------------------------------------------------------------ P&L --}}
    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Booked')" :value="'£'.number_format($f['revenue'] / 100, 2)" icon="pound" />
        <x-stat :label="__('Collected')" :value="'£'.number_format($f['collected'] / 100, 2)" tone="good" />
        <x-stat :label="__('Outstanding')" :value="'£'.number_format($f['outstanding'] / 100, 2)"
                :tone="$f['outstanding'] > 0 ? 'warn' : 'neutral'" :href="route('orders', ['view' => 'unpaid'])" />
        <x-stat :label="__('Estimated profit')" :value="'£'.number_format($f['profit'] / 100, 2)"
                :tone="$f['profit'] >= 0 ? 'good' : 'bad'"
                :hint="__('Booked less expenses and waste')" />
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- --------------------------------------------------- job margin --}}
        <section class="xl:col-span-2">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="label-sm">{{ __('Margin by job — worst first') }}</h2>
                <span class="text-xs text-text-faint">{{ __('Costs are what has actually been recorded against each job') }}</span>
            </div>

            @if ($jobProfit->isEmpty())
                <x-empty icon="chart" :title="__('No priced jobs in this period')"
                         :body="__('Once a job has a price and some recorded costs, its margin appears here.')" />
            @else
                <x-table :head="[__('Job'), __('Date'), __('Revenue'), __('Ingredients'), __('Labour'), __('Waste'), __('Other'), __('Profit'), __('Margin')]"
                         :align="['start', 'start', 'end', 'end', 'end', 'end', 'end', 'end', 'end']">
                    @foreach ($jobProfit as $row)
                        <tr data-row-href="{{ route('orders.show', $row['order']) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <a href="{{ route('orders.show', $row['order']) }}" class="font-medium text-text hover:text-link">
                                    {{ $row['order']->customer_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-text-muted tabular-nums">{{ $row['order']->event_date->format('j M') }}</td>
                            <td class="px-4 py-3 text-end"><x-money :pence="$row['revenue']" /></td>
                            <td class="px-4 py-3 text-end text-text-muted"><x-money :pence="$row['costs']['ingredients']" /></td>
                            <td class="px-4 py-3 text-end text-text-muted"><x-money :pence="$row['costs']['labour']" /></td>
                            <td class="px-4 py-3 text-end text-text-muted"><x-money :pence="$row['costs']['waste']" /></td>
                            <td class="px-4 py-3 text-end text-text-muted"><x-money :pence="$row['costs']['other']" /></td>
                            <td class="px-4 py-3 text-end font-semibold">
                                <x-money :pence="$row['profit']" :tone="$row['profit'] >= 0 ? 'good' : 'bad'" />
                            </td>
                            <td class="px-4 py-3 text-end">
                                @if ($row['margin'] === null)
                                    <span class="text-text-faint">—</span>
                                @else
                                    <x-badge :tone="$row['margin'] >= 40 ? 'good' : ($row['margin'] >= 15 ? 'warn' : 'bad')">
                                        {{ $row['margin'] }}%
                                    </x-badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </section>

        {{-- ------------------------------------------------------- quotes --}}
        <section>
            <h2 class="label-sm mb-3">{{ __('Quotes') }}</h2>
            <div class="card grid grid-cols-2 divide-x divide-line sm:grid-cols-4 rtl:divide-x-reverse">
                @foreach ([
                    [__('Sent'), $quoteStats['sent'], null],
                    [__('Won'), $quoteStats['accepted'], 'text-good'],
                    [__('Lost'), $quoteStats['declined'], 'text-bad'],
                    [__('Win rate'), $quoteStats['win_rate'] === null ? '—' : $quoteStats['win_rate'].'%', null],
                ] as [$label, $value, $tone])
                    <div class="p-4">
                        <p class="label-sm">{{ $label }}</p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums {{ $tone ?? 'text-text' }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ---------------------------------------------------- top dishes --}}
        <section>
            <h2 class="label-sm mb-3">{{ __('Most cooked') }}</h2>
            @if ($topDishes->isEmpty())
                <x-empty icon="chef" :title="__('No dishes recorded')"
                         :body="__('Add dishes to jobs and they will be ranked here.')" />
            @else
                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($topDishes as $dish)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="min-w-0 truncate text-sm text-text">{{ $dish['name'] }}</span>
                            <span class="shrink-0 text-xs text-text-muted tabular-nums">
                                {{ trans_choice('{1}1 guest|[2,*]:count guests', $dish['guests'], ['count' => number_format($dish['guests'])]) }}
                                · {{ trans_choice('{1}1 job|[2,*]:count jobs', $dish['jobs'], ['count' => $dish['jobs']]) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ------------------------------------------------------- waste --}}
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="label-sm">{{ __('Waste by reason') }}</h2>
                <a href="{{ route('reports.export', ['report' => 'waste', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="-my-2 inline-flex min-h-[36px] items-center px-1 text-xs font-semibold text-link hover:underline">{{ __('Export') }}</a>
            </div>

            @if ($wasteByReason->isEmpty())
                <x-empty icon="trash" :title="__('No waste recorded')"
                         :body="__('Either a very good period, or nobody is logging it.')" />
            @else
                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($wasteByReason as $reason => $cost)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="text-sm text-text">{{ \App\Models\WasteLog::reasonLabels()[$reason] ?? $reason }}</span>
                            <span class="text-sm font-semibold text-bad tabular-nums">£{{ number_format($cost, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ---------------------------------------------------- stock value --}}
        <section>
            <h2 class="label-sm mb-3">{{ __('Stock and busiest days') }}</h2>
            <div class="card space-y-4 p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-sm text-text-muted">{{ __('Stock on the shelf right now') }}</span>
                    <span class="text-lg font-semibold text-text tabular-nums">£{{ number_format($stockValue / 100, 2) }}</span>
                </div>

                @if ($busiestDays->isNotEmpty())
                    <div class="border-t border-line pt-4">
                        <p class="label-sm mb-2">{{ __('Busiest days') }}</p>
                        <ul class="space-y-1.5">
                            @foreach ($busiestDays->take(4) as $day => $count)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="text-text-muted">{{ $day }}</span>
                                    <span class="font-semibold text-text tabular-nums">{{ trans_choice('{1}1 job|[2,*]:count jobs', $count, ['count' => $count]) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
