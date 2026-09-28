@extends('layouts.app')

@section('title', __('Quotes'))
@section('subtitle', __('Priced offers, before they become jobs'))

@section('content')
    <x-page-head :title="__('Quotes')" :subtitle="__('Accepting one is what puts a job on the books')">
        <x-btn :href="route('quotes.create')" icon="plus">{{ __('New quote') }}</x-btn>
    </x-page-head>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Out for a decision')" :value="'£'.number_format($openValue / 100, 2)" icon="file-text"
                :hint="__('Draft and sent quotes')" />
        <x-stat :label="__('Won this month')" :value="'£'.number_format($acceptedThisMonth / 100, 2)" tone="good" />
        <x-stat :label="__('Win rate')" :value="$winRate === null ? '—' : $winRate.'%'"
                :hint="$winRate === null ? __('No quotes decided yet') : __('Of quotes that got an answer')" />
    </div>

    <x-filters :reset="route('quotes')">
        <x-field name="q" :label="__('Search')" :value="request('q')" class="min-w-[12rem] flex-1"
                 placeholder="{{ __('Customer, reference or venue') }}" />
    </x-filters>

    <x-tabs param="status" :current="$status" :tabs="[
        '' => __('All').' ('.$counts[''].')',
        'draft' => __('Draft').' ('.$counts['draft'].')',
        'sent' => __('Awaiting reply').' ('.$counts['sent'].')',
        'accepted' => __('Accepted').' ('.$counts['accepted'].')',
        'declined' => __('Declined').' ('.$counts['declined'].')',
    ]" />

    @if ($quotes->isEmpty())
        <x-empty icon="file-text" :title="__('No quotes here')"
                 :body="__('Raise one from an enquiry, or start a fresh quote for a customer who rang.')">
            <x-btn :href="route('quotes.create')" icon="plus">{{ __('New quote') }}</x-btn>
            <x-btn variant="secondary" :href="route('enquiries')">{{ __('See enquiries') }}</x-btn>
        </x-empty>
    @else
        <x-table :head="[__('Quote'), __('Event'), __('Guests'), __('Total'), __('Status'), '']"
                 :align="['start', 'start', 'end', 'end', 'start', 'end']">
            @foreach ($quotes as $quote)
                <tr data-row-href="{{ route('quotes.show', $quote) }}" class="cursor-pointer transition-colors hover:bg-surface-2">
                    <td class="px-4 py-3">
                        <a href="{{ route('quotes.show', $quote) }}" class="font-medium text-text hover:text-link">
                            {{ $quote->customer_name }}
                        </a>
                        <span class="block text-xs text-text-faint tabular-nums">{{ $quote->reference }}</span>
                    </td>
                    <td class="px-4 py-3 text-text-muted">
                        {{ $quote->event_date?->format('j M Y') ?? '—' }}
                        @if ($quote->venue)
                            <span class="block truncate text-xs text-text-faint">{{ $quote->venue }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end tabular-nums text-text-muted">{{ $quote->guests }}</td>
                    <td class="px-4 py-3 text-end font-semibold"><x-money :pence="$quote->total" /></td>
                    <td class="px-4 py-3">
                        <x-badge :tone="$quote->statusTone()">{{ $quote->statusLabel() }}</x-badge>
                        @if ($quote->isExpired())
                            <x-badge tone="warn" class="ms-1">{{ __('Past its date') }}</x-badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end">
                        <x-icon name="arrow-right" class="ms-auto size-4 text-text-faint rtl:rotate-180" />
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-5">{{ $quotes->links() }}</div>
    @endif
@endsection
