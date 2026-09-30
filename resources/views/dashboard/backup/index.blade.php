@extends('layouts.app')

@section('title', __('Backup'))
@section('subtitle', __('A copy of everything in Excel, kept off the website'))

@section('content')
    @php
        $stale = $lastBackup === null || $lastBackup->lt(now()->subDays(7));
        $skipped = (array) session('restore_skipped', []);
    @endphp

    <section class="card mb-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h2 class="text-base font-semibold text-text">{{ __('Download everything') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-text-muted">
                    {{ __('One zip file with every order, item, payment, customer, member of staff, shift, wage and stock item, as Excel sheets. Save it on the office computer and on a USB stick or Google Drive.') }}
                </p>
                <p class="mt-3 text-sm font-semibold {{ $stale ? 'text-warn' : 'text-good' }}">
                    @if ($lastBackup)
                        {{ __('Last downloaded :when (:date).', ['when' => $lastBackup->diffForHumans(), 'date' => $lastBackup->format('j M Y, H:i')]) }}
                    @else
                        {{ __('No backup downloaded yet.') }}
                    @endif
                    @if ($stale)
                        {{ __('Download one every Monday after entering the week\'s orders.') }}
                    @endif
                </p>
            </div>
            <x-btn icon="download" :href="route('backup.download')">{{ __('Download everything') }}</x-btn>
        </div>

        <dl class="mt-5 grid gap-3 border-t border-line pt-5 text-sm sm:grid-cols-3">
            <div><dt class="text-text-muted">{{ __('Orders') }}</dt><dd class="font-semibold tabular-nums text-text">{{ $counts['orders'] }}</dd></div>
            <div><dt class="text-text-muted">{{ __('Payments') }}</dt><dd class="font-semibold tabular-nums text-text">{{ $counts['payments'] }}</dd></div>
            <div><dt class="text-text-muted">{{ __('Wages paid') }}</dt><dd class="font-semibold tabular-nums text-text">{{ $counts['wages'] }}</dd></div>
        </dl>
    </section>

    <details class="card p-6" @if ($skipped !== [] || $errors->has('file')) open @endif>
        <summary class="tap cursor-pointer list-none text-base font-semibold text-text">
            {{ __('Restore from a backup') }}
        </summary>

        <div class="mt-4 space-y-4 border-t border-line pt-4">
            <ul class="list-inside list-disc space-y-1 text-sm leading-relaxed text-text-muted">
                <li>{{ __('Upload a zip saved from "Download everything", as it is.') }}</li>
                <li>{{ __('Orders, payments and wages that are missing from the website are put back. Orders are matched by their reference.') }}</li>
                <li class="font-semibold text-text">{{ __('Nothing already on the website is changed or deleted, so it is safe to upload the same file twice.') }}</li>
                <li>{{ __('Staff, stock and shifts go back through "Import from Excel" on their own pages, using the files inside the zip. Do staff first, so wages can find each person.') }}</li>
            </ul>

            @if ($skipped !== [])
                <div class="rounded-xl border border-warn/30 bg-warn-bg px-4 py-3 text-sm text-warn">
                    <p class="font-semibold">{{ __('These rows were skipped:') }}</p>
                    <ul class="mt-2 max-h-64 list-inside list-disc space-y-1 overflow-y-auto">
                        @foreach ($skipped as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-field name="file" type="file" :label="__('Backup zip')" accept=".zip,application/zip" required
                         :hint="__('Up to 20 MB.')" />
                <x-btn type="submit" variant="secondary">{{ __('Restore') }}</x-btn>
            </form>
        </div>
    </details>
@endsection
