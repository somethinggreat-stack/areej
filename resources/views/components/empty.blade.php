@props(['title', 'body' => null, 'icon' => 'inbox'])

<div class="card flex flex-col items-center px-6 py-12 text-center">
    <span class="grid size-12 place-items-center rounded-full bg-surface-2 text-text-faint">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <p class="mt-4 text-sm font-semibold text-text">{{ $title }}</p>
    @if ($body)
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-text-muted">{{ $body }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
