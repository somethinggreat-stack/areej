@extends('layouts.app')

@section('title', __('Equipment'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
        <div class="space-y-4">
            @if ($out->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('Out on jobs') }}</h2>
                        <p class="mt-0.5 text-xs text-text-muted">{{ __('Book it back in when the van is unloaded.') }}</p>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($out as $assignment)
                            <li class="p-5">
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-text">
                                            {{ $assignment->quantity_out }} × {{ $assignment->equipmentItem->displayName() }}
                                        </p>
                                        <p class="text-xs text-text-muted">
                                            {{ $assignment->order->customer_name }} ·
                                            {{ $assignment->order->event_date->format('j M') }}
                                            @if ($assignment->order->venue) · {{ $assignment->order->venue }} @endif
                                        </p>
                                    </div>

                                    <form method="POST" action="{{ route('equipment.return', $assignment) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        <div class="w-20">
                                            <label class="block text-[0.68rem] font-semibold text-text-muted" for="ret-{{ $assignment->id }}">{{ __('Back') }}</label>
                                            <input id="ret-{{ $assignment->id }}" type="number" min="0" name="quantity_returned" value="{{ $assignment->quantity_out }}"
                                                   class="tap mt-1 w-full rounded-lg border border-line-strong px-2 py-2 text-end text-sm tabular-nums outline-none focus:border-gold">
                                        </div>
                                        <div class="w-20">
                                            <label class="block text-[0.68rem] font-semibold text-text-muted" for="lost-{{ $assignment->id }}">{{ __('Lost') }}</label>
                                            <input id="lost-{{ $assignment->id }}" type="number" min="0" name="quantity_lost" value="0"
                                                   class="tap mt-1 w-full rounded-lg border border-line-strong px-2 py-2 text-end text-sm tabular-nums outline-none focus:border-gold">
                                        </div>
                                        <div class="w-20">
                                            <label class="block text-[0.68rem] font-semibold text-text-muted" for="dmg-{{ $assignment->id }}">{{ __('Damaged') }}</label>
                                            <input id="dmg-{{ $assignment->id }}" type="number" min="0" name="quantity_damaged" value="0"
                                                   class="tap mt-1 w-full rounded-lg border border-line-strong px-2 py-2 text-end text-sm tabular-nums outline-none focus:border-gold">
                                        </div>
                                        <x-btn type="submit" variant="secondary">{{ __('Book in') }}</x-btn>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($items->isEmpty())
                <x-empty :title="__('No equipment on the register')" :body="__('Add your chafing dishes, crockery and urns so you can see what is out and what came back.')" />
            @else
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-text">{{ __('The register') }}</h2>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($items as $item)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-text">{{ $item->displayName() }}</span>
                                    <span class="block text-xs text-text-muted">{{ __(ucfirst($item->category)) }}</span>
                                </span>
                                <span class="shrink-0 text-end">
                                    <span class="block text-sm font-semibold text-text tabular-nums">
                                        {{ __(':available of :owned', ['available' => $item->quantityAvailable(), 'owned' => $item->quantity_owned]) }}
                                    </span>
                                    @if ($item->quantityOut() > 0)
                                        <span class="block text-xs text-warn">{{ __(':n out', ['n' => $item->quantityOut()]) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @if ($items->isNotEmpty() && $orders->isNotEmpty())
                <form method="POST" action="{{ route('equipment.store') }}" class="card space-y-4 p-5">
                    @csrf
                    <h2 class="text-sm font-semibold text-text">{{ __('Send kit to a job') }}</h2>
                    <x-field name="order_id" type="select" :label="__('Job')" required
                             :options="$orders->mapWithKeys(fn ($o) => [$o->id => $o->customer_name . ' — ' . $o->event_date->format('j M')])->all()" />
                    <x-field name="equipment_item_id" type="select" :label="__('Item')" required
                             :options="$items->mapWithKeys(fn ($i) => [$i->id => $i->displayName() . ' (' . $i->quantityAvailable() . ' ' . __('available') . ')'])->all()" />
                    <x-field name="quantity_out" type="number" min="1" :label="__('How many')" required />
                    <x-btn type="submit">{{ __('Send it out') }}</x-btn>
                </form>
            @endif

            <details class="card p-5">
                <summary class="tap cursor-pointer list-none text-sm font-semibold text-text">{{ __('Add to the register') }}</summary>
                <form method="POST" action="{{ route('equipment.store') }}" class="mt-4 space-y-4 border-t border-line pt-4">
                    @csrf
                    <x-field name="name_en" :label="__('Name (English)')" required />
                    <x-field name="name_ur" :label="__('Name (Urdu)')" dir="rtl" />
                    <x-field name="category" type="select" :label="__('Kind')" required
                             :options="[
                                'chafing' => __('Chafing dishes and burners'),
                                'crockery' => __('Crockery'),
                                'cutlery' => __('Cutlery'),
                                'serving' => __('Serving dishes'),
                                'cooking' => __('Cooking equipment'),
                                'other' => __('Other'),
                             ]" />
                    <x-field name="quantity_owned" type="number" min="0" :label="__('How many we own')" required :value="0" />
                    @if (auth()->user()->canSeeFinancials())
                        <x-field name="replacement_cost" type="number" step="0.01" min="0" suffix="£" :label="__('Replacement cost each')" />
                    @endif
                    <x-btn type="submit" variant="secondary">{{ __('Add') }}</x-btn>
                </form>
            </details>
        </div>
    </div>
@endsection
