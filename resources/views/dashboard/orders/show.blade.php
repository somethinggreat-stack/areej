@extends('layouts.app')

@section('title', $order->customer_name)
@section('subtitle', $order->reference.' · '.$order->event_date->format('l j F Y'))

@section('content')
    @php
        $money = auth()->user()->canSeeFinancials();
        $shortfalls = $requirements->where('shortfall', '>', 0);
        $deducted = $order->dishes->isNotEmpty() && $order->dishes->every(fn ($d) => $d->ingredients_deducted);
    @endphp

    <x-page-head :title="$order->customer_name" :back="route('orders')" :backLabel="__('Orders')"
                 :subtitle="$order->reference.' · '.$order->event_date->format('l j F Y')">
        @if ($order->phone)
            <x-btn variant="secondary" size="sm" :href="'tel:'.preg_replace('/\s+/', '', $order->phone)">{{ $order->phone }}</x-btn>
        @endif
        @if ($money)
            <x-btn variant="secondary" size="sm" icon="receipt" :href="route('orders.invoice', $order)" target="_blank">{{ __('Invoice') }}</x-btn>
        @endif
    </x-page-head>

    {{-- ------------------------------------------------------- headline --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Status')" :value="$order->statusLabel()" :tone="$order->statusTone()"
                :hint="$order->event_date->isFuture() ? $order->event_date->diffForHumans() : $order->event_date->format('j M Y')" />

        <x-stat :label="__('Guests')" :value="number_format($order->guests)" icon="users"
                :hint="$order->staff_required ? trans_choice('{1}1 staff needed|[2,*]:count staff needed', $order->staff_required, ['count' => $order->staff_required]) : null" />

        @if ($money)
            <x-stat :label="__('Outstanding')" :value="'£'.number_format($order->balanceInPounds(), 2)"
                    :tone="$order->balanceAmount() > 0 ? 'warn' : 'good'"
                    :hint="__(':paid of :total collected', ['paid' => '£'.number_format($order->paidInPounds(), 2), 'total' => '£'.number_format($order->totalInPounds(), 2)])" />

            <x-stat :label="__('Margin')"
                    :value="$order->marginPercent() === null ? '—' : $order->marginPercent().'%'"
                    :tone="$order->marginPercent() === null ? 'neutral' : ($order->marginPercent() >= 40 ? 'good' : ($order->marginPercent() >= 15 ? 'warn' : 'bad'))"
                    :hint="$order->hasCostData() ? __('Profit :n', ['n' => '£'.number_format(($order->total_amount - $costs['total']) / 100, 2)]) : __('No costs recorded yet')" />
        @else
            <x-stat :label="__('Prep')" :value="$order->taskProgress().'%'" icon="flame"
                    :hint="trans_choice('{0}No checklist|{1}1 task|[2,*]:count tasks', $order->tasks->count(), ['count' => $order->tasks->count()])" />
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="space-y-4">
            {{-- --------------------------------------------- what to cook --}}
            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-text">{{ __('What we are cooking') }}</h2>
                        <p class="mt-0.5 text-xs text-text-muted">{{ __('Dishes and head counts drive the ingredient list below.') }}</p>
                    </div>
                    @if ($deducted)
                        <x-badge tone="good" dot>{{ __('Ingredients taken off stock') }}</x-badge>
                    @endif
                </div>

                @if ($order->dishes->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-text-muted">
                        {{ __('No dishes chosen yet. Add them so the kitchen knows what to make and the stock can be worked out.') }}
                    </p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($order->dishes as $orderDish)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-text">{{ $orderDish->dish?->displayName() }}</span>
                                    <span class="block text-xs text-text-muted">
                                        {{ $orderDish->dish?->courseLabel() }}
                                        @unless ($orderDish->dish?->hasRecipe())
                                            · <span class="font-semibold text-warn">{{ __('no recipe') }}</span>
                                        @endunless
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="text-sm font-semibold text-text tabular-nums">
                                        {{ trans_choice('{1}1 guest|[2,*]:count guests', $orderDish->guests, ['count' => $orderDish->guests]) }}
                                    </span>
                                    @unless ($orderDish->ingredients_deducted)
                                        <form method="POST" action="{{ route('orders.dishes.destroy', $orderDish) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-btn type="submit" size="sm" variant="ghost" :confirm="__('Remove this dish from the job?')">
                                                <x-icon name="trash" class="size-3.5 text-bad" />
                                                <span class="sr-only">{{ __('Remove') }}</span>
                                            </x-btn>
                                        </form>
                                    @endunless
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('orders.dishes.store', $order) }}" class="flex flex-wrap items-end gap-3 border-t border-line bg-surface-2 p-5">
                    @csrf
                    <x-field name="dish_id" type="select" :label="__('Dish')" required class="min-w-[12rem] flex-1"
                             :options="$dishes->mapWithKeys(fn ($d) => [$d->id => $d->displayName().' — '.$d->courseLabel()])->all()" />
                    <x-field name="guests" type="number" min="1" :label="__('For how many')" :value="$order->guests" required class="w-36" />
                    <x-btn type="submit" variant="secondary" icon="plus">{{ __('Add dish') }}</x-btn>
                </form>
            </section>

            {{-- ------------------------------------------ ingredients need --}}
            @if ($requirements->isNotEmpty())
                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-text">{{ __('Ingredients needed') }}</h2>
                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ __('Worked out from the recipes and the head counts above.') }}
                            </p>
                        </div>
                        @if ($money)
                            <span class="text-sm font-semibold text-text tabular-nums">
                                £{{ number_format($requirements->sum('cost'), 2) }}
                            </span>
                        @endif
                    </div>

                    @if ($shortfalls->isNotEmpty())
                        <div class="border-b border-line bg-warn-bg px-5 py-3">
                            <p class="text-sm font-semibold text-warn">
                                {{ trans_choice('{1}1 item is short|[2,*]:count items are short', $shortfalls->count(), ['count' => $shortfalls->count()]) }}
                            </p>
                            <p class="mt-0.5 text-xs text-warn">{{ __('Order these in before the job, or the kitchen will run out.') }}</p>
                            <div class="mt-2">
                                <x-btn size="sm" variant="secondary" :href="route('purchase-orders')">{{ __('Go to purchasing') }}</x-btn>
                            </div>
                        </div>
                    @endif

                    <x-table :head="[__('Ingredient'), __('Needed'), __('On hand'), __('Short'), $money ? __('Cost') : '']"
                             :align="['start', 'end', 'end', 'end', 'end']">
                        @foreach ($requirements as $row)
                            <tr>
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('inventory.show', $row['item']) }}" class="font-medium text-text hover:text-link">
                                        {{ $row['item']->displayName() }}
                                    </a>
                                    <span class="block truncate text-xs text-text-faint">{{ implode(', ', $row['dishes']) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-end font-semibold tabular-nums">{{ qty($row['required']) }} {{ $row['item']->unit }}</td>
                                <td class="px-4 py-2.5 text-end tabular-nums text-text-muted">{{ qty($row['on_hand']) }}</td>
                                <td class="px-4 py-2.5 text-end tabular-nums">
                                    @if ($row['shortfall'] > 0)
                                        <span class="font-semibold text-bad">{{ qty($row['shortfall']) }}</span>
                                    @else
                                        <x-icon name="check" class="ms-auto size-4 text-good" />
                                    @endif
                                </td>
                                @if ($money)
                                    <td class="px-4 py-2.5 text-end tabular-nums text-text-muted">£{{ number_format($row['cost'], 2) }}</td>
                                @else
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>

                    @unless ($deducted)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-2 p-5">
                            <p class="text-xs text-text-muted">
                                {{ __('Once the job is cooked, take these off the shelf so stock stays honest.') }}
                            </p>
                            <form method="POST" action="{{ route('orders.deduct', $order) }}">
                                @csrf
                                <x-btn type="submit" variant="secondary" :disabled="$shortfalls->isNotEmpty()"
                                       :confirm="__('Take all these ingredients off stock? This cannot be undone in one click.')">
                                    {{ __('Deduct from stock') }}
                                </x-btn>
                            </form>
                        </div>
                    @endunless
                </section>
            @endif

            {{-- ------------------------------------------------------ prep --}}
            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-text">{{ __('Prep checklist') }}</h2>
                    <div class="flex items-center gap-3">
                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-surface-3">
                            <div class="h-full rounded-full bg-good" style="width: {{ $order->taskProgress() }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-text tabular-nums">{{ $order->taskProgress() }}%</span>
                    </div>
                </div>

                @if ($order->tasks->isEmpty())
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-6">
                        <p class="text-sm text-text-muted">{{ __('No checklist yet.') }}</p>
                        <form method="POST" action="{{ route('orders.tasks.standard', $order) }}">
                            @csrf
                            <x-btn type="submit" variant="secondary" size="sm">{{ __('Add the standard checklist') }}</x-btn>
                        </form>
                    </div>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($order->tasks as $task)
                            <li class="flex items-center gap-3 px-5 py-2.5 {{ $task->is_done ? 'opacity-55' : '' }}">
                                <form method="POST" action="{{ route('prep.toggle', $task) }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" aria-label="{{ __('Tick off :task', ['task' => $task->title]) }}"
                                            class="tap grid place-items-center rounded-lg border transition-colors
                                                   {{ $task->is_done ? 'border-good bg-good text-surface' : 'border-line-strong text-text-faint hover:border-good hover:text-good' }}">
                                        <x-icon name="check" class="size-4" />
                                    </button>
                                </form>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-text {{ $task->is_done ? 'line-through' : 'font-medium' }}">{{ $task->title }}</span>
                                    <span class="block text-xs text-text-faint">
                                        {{ $task->stageLabel() }}
                                        @if ($task->dueAt()) · {{ $task->dueAt()->format('D H:i') }} @endif
                                        @if ($task->assignee) · {{ $task->assignee->full_name }} @endif
                                    </span>
                                </span>
                                @if ($task->isOverdue())<x-badge tone="bad">{{ __('Overdue') }}</x-badge>@endif
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('orders.tasks.store', $order) }}" class="flex flex-wrap items-end gap-3 border-t border-line bg-surface-2 p-5">
                        @csrf
                        <x-field name="title" :label="__('Task')" required class="min-w-[12rem] flex-1" />
                        <x-field name="stage" type="select" :label="__('Stage')" required class="w-36"
                                 :options="\App\Models\OrderTask::stageLabels()" />
                        <x-field name="due_offset_hours" type="number" min="0" suffix="h" :label="__('Before serving')" :value="6" required class="w-36" />
                        <x-btn type="submit" variant="secondary" icon="plus">{{ __('Add') }}</x-btn>
                    </form>
                @endif
            </section>

            {{-- --------------------------------------------------- billing --}}
            @if ($money)
                <section class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Invoice lines') }}</h2>
                    </div>

                    @if ($order->items->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-text-muted">
                            {{ __('No priced lines. The order total is used as it stands.') }}
                        </p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($order->items as $item)
                                <li class="flex items-center justify-between gap-4 px-5 py-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-text">{{ $item->description }}</span>
                                        <span class="block text-xs text-text-muted tabular-nums">
                                            {{ qty($item->quantity, 2) }} {{ $item->unit }} × £{{ number_format($item->unitPriceInPounds(), 2) }}
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-3">
                                        <x-money :pence="$item->line_total" class="text-sm font-semibold" />
                                        <form method="POST" action="{{ route('orders.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-btn type="submit" size="sm" variant="ghost" :confirm="__('Remove this line?')">
                                                <x-icon name="trash" class="size-3.5 text-bad" />
                                                <span class="sr-only">{{ __('Remove') }}</span>
                                            </x-btn>
                                        </form>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('orders.items.store', $order) }}" class="flex flex-wrap items-end gap-3 border-t border-line bg-surface-2 p-5" data-line-scope>
                        @csrf
                        <x-field name="description" :label="__('Line')" required class="min-w-[11rem] flex-1" />
                        <x-field name="quantity" type="number" step="0.01" min="0" :label="__('Qty')" :value="1" required class="w-24" data-line-qty />
                        <x-field name="unit" :label="__('Unit')" class="w-24" />
                        <x-field name="unit_price" type="number" step="0.01" min="0" prefix="£" :label="__('Each')" :value="0" required class="w-28" data-line-price />
                        <span class="pb-2 text-sm font-semibold text-text-muted"><span data-line-total class="tabular-nums">£0.00</span></span>
                        <x-btn type="submit" variant="secondary" icon="plus">{{ __('Add') }}</x-btn>
                    </form>
                </section>

                <section class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Payments') }}</h2>
                        <p class="mt-0.5 text-xs text-text-muted">{{ __('Record each one as it comes in.') }}</p>
                    </div>

                    @if ($order->payments->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-text-muted">{{ __('Nothing received yet.') }}</p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($order->payments as $payment)
                                <li class="flex items-center justify-between gap-4 px-5 py-3">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-text">{{ $payment->kindLabel() }} · {{ $payment->methodLabel() }}</span>
                                        <span class="block truncate text-xs text-text-muted">
                                            {{ $payment->paid_on->format('j M Y') }}
                                            @if ($payment->recorder) · {{ $payment->recorder->name }} @endif
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-3">
                                        <x-money :pence="$payment->amount" :tone="$payment->kind === 'refund' ? 'bad' : 'good'" class="text-sm font-semibold" />
                                        <form method="POST" action="{{ route('payments.destroy', $payment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-btn type="submit" size="sm" variant="ghost" :confirm="__('Remove this payment?')">
                                                <x-icon name="trash" class="size-3.5 text-bad" />
                                                <span class="sr-only">{{ __('Remove') }}</span>
                                            </x-btn>
                                        </form>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('orders.payments.store', $order) }}" class="flex flex-wrap items-end gap-3 border-t border-line bg-surface-2 p-5">
                        @csrf
                        <x-field name="amount" type="number" step="0.01" min="0" prefix="£" :label="__('Amount')" required class="w-28" />
                        <x-field name="kind" type="select" :label="__('What for')" class="w-36" :options="\App\Models\OrderPayment::kindLabels()" />
                        <x-field name="method" type="select" :label="__('How')" class="w-36" :options="\App\Models\OrderPayment::methodLabels()" />
                        <x-field name="paid_on" type="date" :label="__('When')" :value="now()->toDateString()" required class="w-40" />
                        <x-btn type="submit" variant="secondary">{{ __('Record') }}</x-btn>
                    </form>
                </section>

                {{-- Cost breakdown, so a thin margin can be explained. --}}
                <section class="card p-5">
                    <h2 class="text-sm font-semibold text-text">{{ __('Where the money went') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ([
                            __('Ingredients used') => $costs['ingredients'],
                            __('Labour on the day') => $costs['labour'],
                            __('Waste') => $costs['waste'],
                            __('Other expenses') => $costs['other'],
                        ] as $label => $amount)
                            <li class="flex justify-between">
                                <span class="text-text-muted">{{ $label }}</span>
                                <x-money :pence="$amount" class="text-text" />
                            </li>
                        @endforeach
                        <li class="flex justify-between border-t border-line pt-2 font-semibold">
                            <span>{{ __('Total cost') }}</span>
                            <x-money :pence="$costs['total']" />
                        </li>
                        <li class="flex justify-between text-base font-semibold">
                            <span>{{ __('Profit') }}</span>
                            <x-money :pence="$order->total_amount - $costs['total']"
                                     :tone="($order->total_amount - $costs['total']) >= 0 ? 'good' : 'bad'" />
                        </li>
                    </ul>
                </section>
            @endif
        </div>

        {{-- ------------------------------------------------------- sidebar --}}
        <div class="space-y-4">
            @if ($order->shifts->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Staff on this job') }}</h2>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($order->shifts as $shift)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span class="min-w-0 truncate text-sm text-text">{{ $shift->staff->full_name }}</span>
                                <x-badge :tone="match ($shift->status) { 'approved', 'closed' => 'good', 'open' => 'info', 'absent' => 'bad', default => 'neutral' }">
                                    {{ $shift->statusLabel() }}
                                </x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('orders.update', $order) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-text">{{ __('Details') }}</h2>

                <x-field name="customer_name" :label="__('Customer')" :value="$order->customer_name" required />
                <x-field name="phone" type="tel" :label="__('Phone')" :value="$order->phone" />
                <x-field name="email" type="email" :label="__('Email')" :value="$order->email" />
                <x-field name="event_date" type="date" :label="__('Date')" :value="$order->event_date->toDateString()" required />
                <x-field name="serve_time" type="time" :label="__('Serving time')" :value="$order->serve_time" />
                <x-field name="guests" type="number" min="0" :label="__('Guests')" :value="$order->guests" />
                <x-field name="order_type" :label="__('Occasion')" :value="$order->order_type" />
                <x-field name="venue" :label="__('Venue')" :value="$order->venue" />
                <x-field name="venue_address" type="textarea" :rows="2" :label="__('Address')" :value="$order->venue_address" />
                <x-field name="service_style" type="select" :label="__('Service')" required :value="$order->service_style"
                         :options="\App\Models\Order::serviceStyleLabels()" />
                <x-field name="staff_required" type="number" min="0" :label="__('Staff needed')" :value="$order->staff_required" />
                <x-field name="dietary" type="textarea" :rows="2" :label="__('Dietary')" :value="$order->dietary" />
                <x-field name="menu_notes" type="textarea" :rows="3" :label="__('Menu notes')" :value="$order->menu_notes" />

                @if ($money)
                    <x-field name="total_amount" type="number" step="0.01" min="0" prefix="£" :label="__('Total agreed')" :value="$order->totalInPounds()" />
                    <x-field name="deposit_due" type="number" step="0.01" min="0" prefix="£" :label="__('Deposit due')" :value="$order->deposit_due / 100" />
                @endif

                <x-field name="status" type="select" :label="__('Status')" required :value="$order->status"
                         :options="\App\Models\Order::statusLabels()" />
                <x-field name="source" :label="__('Came in by')" :value="$order->source" />
                <x-field name="notes" type="textarea" :rows="3" :label="__('Internal notes')" :value="$order->notes" />

                <x-btn type="submit">{{ __('Save changes') }}</x-btn>
            </form>

            @if ($order->paidAmount() === 0)
                <form method="POST" action="{{ route('orders.destroy', $order) }}" class="card p-5">
                    @csrf
                    @method('DELETE')
                    <x-btn type="submit" variant="danger" class="w-full"
                           :confirm="__('Delete job :ref? This cannot be undone.', ['ref' => $order->reference])">
                        {{ __('Delete this job') }}
                    </x-btn>
                </form>
            @endif
        </div>
    </div>
@endsection
