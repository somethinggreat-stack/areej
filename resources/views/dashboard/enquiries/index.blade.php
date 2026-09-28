@extends('layouts.app')

@section('title', __('Enquiries'))
@section('subtitle', __('Quote requests from the website'))

@section('content')
    <x-page-head :title="__('Enquiries')" :subtitle="__('Quote requests from the website')" />

    <x-tabs param="status" :current="$status" :tabs="[
        '' => __('All'),
        'new' => __('New').' ('.$counts['new'].')',
        'contacted' => __('Contacted').' ('.$counts['contacted'].')',
        'quoted' => __('Quoted').' ('.$counts['quoted'].')',
        'won' => __('Won').' ('.$counts['won'].')',
        'lost' => __('Lost').' ('.$counts['lost'].')',
    ]" />

    @if ($enquiries->isEmpty())
        <x-empty icon="inbox" :title="__('Nothing here')"
                 :body="__('Quote requests from the website land in this list the moment they are submitted.')" />
    @else
        <div class="card divide-y divide-line overflow-hidden">
            @foreach ($enquiries as $enquiry)
                <a href="{{ route('enquiries.show', $enquiry) }}" class="tap flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-surface-2">
                    <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-semibold text-text">{{ $enquiry->name }}</span>
                            <x-badge :tone="match ($enquiry->status) { 'new' => 'warn', 'won' => 'good', 'lost' => 'bad', default => 'info' }">
                                {{ __(ucfirst($enquiry->status)) }}
                            </x-badge>
                            @if ($enquiry->isUrgent())<x-badge tone="bad">{{ __('Soon') }}</x-badge>@endif
                            @if ($enquiry->order)<x-badge tone="good">{{ $enquiry->order->reference }}</x-badge>@endif
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-text-muted">
                            <span class="tabular-nums">{{ $enquiry->reference }}</span>
                            @if ($enquiry->event_type) · {{ $enquiry->event_type }} @endif
                            @if ($enquiry->event_date) · {{ $enquiry->event_date->format('j M Y') }} @endif
                            @if ($enquiry->guests) · {{ trans_choice('{1}1 guest|[2,*]:count guests', $enquiry->guests, ['count' => $enquiry->guests]) }} @endif
                            · {{ $enquiry->created_at->diffForHumans() }}
                        </span>
                    </span>
                    <span class="shrink-0 text-xs text-text-faint">{{ $enquiry->assignee?->name ?? __('Unassigned') }}</span>
                </a>
            @endforeach
        </div>

        <div class="mt-5">{{ $enquiries->links() }}</div>
    @endif
@endsection
