@props(['action' => null, 'reset' => null])

{{-- Filters submit with GET so every view is a shareable, bookmarkable URL. --}}
<form method="GET" @if ($action) action="{{ $action }}" @endif
      class="card mb-5 flex flex-wrap items-end gap-3 p-4">
    {{ $slot }}

    <div class="flex items-center gap-2">
        <x-btn type="submit" variant="secondary" icon="search">{{ __('Apply') }}</x-btn>
        @if ($reset && request()->hasAny(array_keys(request()->query())))
            <x-btn variant="ghost" size="sm" :href="$reset">{{ __('Clear') }}</x-btn>
        @endif
    </div>
</form>
