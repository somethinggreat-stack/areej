{{--
    "Import from Excel" for the staff or stock sheet. Folded away until needed,
    and opened again after an upload so the result and any skipped rows show.
--}}
@php
    $justImported = session('import_sheet') === $sheet;
    $skipped = $justImported ? (array) session('import_skipped', []) : [];
@endphp

<details class="card p-5" @if ($justImported || $errors->has('file')) open @endif>
    <summary class="tap cursor-pointer list-none text-sm font-semibold text-text">
        {{ __('Import from Excel') }}
    </summary>

    <div class="mt-4 space-y-4 border-t border-line pt-4">
        <ul class="list-inside list-disc space-y-1 text-sm leading-relaxed text-text-muted">
            <li>{{ __('Export to Excel first, or download a blank template.') }}</li>
            <li>{{ __('Add or change rows. Keep the first row (the headings) as it is.') }}</li>
            <li>{{ __('In Excel use File → Save As and choose "CSV UTF-8".') }}</li>
            <li>{{ __('Upload the file here.') }}
                {{ $sheet === 'staff'
                    ? __('People are matched by full name: a new name is added, an existing one is updated.')
                    : __('Items are matched by English name: a new name is added, an existing one is updated.') }}
            </li>
            @if ($sheet === 'stock')
                <li class="font-semibold text-warn">{{ __('Quantities are only set for new items — use a stock count to correct existing ones.') }}</li>
            @endif
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

        <form method="POST" action="{{ route('excel.'.$sheet.'.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-field name="file" type="file" :label="__('CSV file')" accept=".csv,text/csv" required
                     :hint="__('Up to 2 MB.')" />
            <div class="flex flex-wrap items-center gap-2">
                <x-btn type="submit">{{ __('Import') }}</x-btn>
                <x-btn variant="ghost" size="sm" icon="download" :href="route('excel.'.$sheet.'.template')">
                    {{ __('Download a blank template') }}
                </x-btn>
            </div>
        </form>
    </div>
</details>
