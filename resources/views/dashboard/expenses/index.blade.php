@extends('layouts.app')

@section('title', __('Expenses'))
@section('subtitle', $from->format('j M').' – '.$to->format('j M Y'))

@section('content')
    <x-page-head :title="__('Expenses')" :subtitle="__('Money out that is not stock')" />

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div>
            <x-filters :reset="route('expenses')">
                <x-field name="from" type="date" :label="__('From')" :value="$from->toDateString()" class="w-40" />
                <x-field name="to" type="date" :label="__('To')" :value="$to->toDateString()" class="w-40" />
                <x-field name="category" type="select" :label="__('Category')" :value="$category" class="min-w-[10rem]"
                         :options="['' => __('All categories')] + \App\Models\Expense::categoryLabels()" />
            </x-filters>

            <div class="mb-5 grid gap-4 sm:grid-cols-2">
                <x-stat :label="__('Total in this period')" :value="'£'.number_format($total / 100, 2)" icon="receipt" />
                <x-stat :label="__('Entries')" :value="$expenses->total()" />
            </div>

            @if ($byCategory->isNotEmpty())
                <div class="card mb-5 p-5">
                    <h2 class="text-sm font-semibold text-text">{{ __('Where it went') }}</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($byCategory as $key => $amount)
                            @php $share = $total > 0 ? round($amount / $total * 100) : 0; @endphp
                            <li>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="text-text-muted">{{ \App\Models\Expense::categoryLabels()[$key] ?? $key }}</span>
                                    <span class="font-semibold text-text tabular-nums">£{{ number_format($amount / 100, 2) }}</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-3">
                                    <div class="h-full rounded-full bg-royal-lit" style="width: {{ $share }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($expenses->isEmpty())
                <x-empty icon="receipt" :title="__('Nothing recorded in this period')"
                         :body="__('Fuel, hire, repairs and wages all belong here so the profit figures mean something.')" />
            @else
                <x-table :head="[__('Date'), __('Description'), __('Category'), __('Amount'), '']"
                         :align="['start', 'start', 'start', 'end', 'end']">
                    @foreach ($expenses as $expense)
                        <tr class="transition-colors hover:bg-surface-2">
                            <td class="px-4 py-3 whitespace-nowrap text-text-muted tabular-nums">{{ $expense->spent_on->format('j M') }}</td>
                            <td class="px-4 py-3">
                                <span class="block font-medium text-text">{{ $expense->description }}</span>
                                <span class="block text-xs text-text-faint">
                                    @if ($expense->supplier){{ $expense->supplier->name }}@endif
                                    @if ($expense->order) · {{ $expense->order->reference }} @endif
                                    @if ($expense->recorder) · {{ $expense->recorder->name }} @endif
                                </span>
                            </td>
                            <td class="px-4 py-3"><x-badge>{{ $expense->categoryLabel() }}</x-badge></td>
                            <td class="px-4 py-3 text-end font-semibold"><x-money :pence="$expense->amount" /></td>
                            <td class="px-4 py-3 text-end">
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-btn type="submit" size="sm" variant="ghost"
                                           :confirm="__('Delete this expense?')">
                                        <x-icon name="trash" class="size-3.5 text-bad" />
                                        <span class="sr-only">{{ __('Delete') }}</span>
                                    </x-btn>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="mt-5">{{ $expenses->links() }}</div>
            @endif
        </div>

        <form method="POST" action="{{ route('expenses.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            <h2 class="text-sm font-semibold text-text">{{ __('Record an expense') }}</h2>

            <x-field name="description" :label="__('What was it')" required placeholder="{{ __('Diesel for the van') }}" />
            <x-field name="amount" type="number" step="0.01" min="0" prefix="£" :label="__('Amount')" required />
            <x-field name="category" type="select" :label="__('Category')" required
                     :options="\App\Models\Expense::categoryLabels()" />
            <x-field name="spent_on" type="date" :label="__('When')" :value="now()->toDateString()" required />
            <x-field name="method" type="select" :label="__('Paid by')"
                     :options="\App\Models\OrderPayment::methodLabels()" />
            <x-field name="order_id" type="select" :label="__('Against a job')"
                     :hint="__('Leave blank for an overhead')"
                     :options="['' => __('Not job specific')] + $orders->mapWithKeys(fn ($o) => [$o->id => $o->customer_name.' — '.$o->event_date->format('j M')])->all()" />
            <x-field name="supplier_id" type="select" :label="__('Supplier')"
                     :options="['' => __('None')] + $suppliers->pluck('name', 'id')->all()" />
            <x-field name="reference" :label="__('Reference')" :hint="__('Receipt or invoice number')" />

            <x-btn type="submit" class="w-full">{{ __('Record it') }}</x-btn>
        </form>
    </div>
@endsection
