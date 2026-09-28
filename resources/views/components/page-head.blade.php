@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-text-muted transition-colors hover:text-text">
                <x-icon name="arrow-left" class="size-3.5 rtl:rotate-180" />
                {{ $backLabel ?? __('Back') }}
            </a>
        @endif
        <h2 class="truncate text-xl font-semibold text-text">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
