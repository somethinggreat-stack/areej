@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'confirm' => null,
])

@php
    $variants = [
        'primary'   => 'bg-gold text-ink hover:bg-gold-lit focus-visible:outline-gold',
        'secondary' => 'border border-line-strong bg-surface text-text hover:bg-surface-2',
        'ghost'     => 'text-text-muted hover:bg-surface-2 hover:text-text',
        'danger'    => 'border border-bad/35 bg-surface text-bad hover:bg-bad-bg',
        'dark'      => 'bg-navy text-cream hover:bg-navy-2',
    ];

    $sizes = [
        'sm' => 'h-9 px-3 text-[0.8rem] gap-1.5',
        'md' => 'tap px-4 text-sm gap-2',
        'lg' => 'h-12 px-6 text-sm gap-2.5',
    ];

    $base = 'inline-flex shrink-0 items-center justify-center rounded-lg font-semibold transition-colors '
        . 'disabled:pointer-events-none disabled:opacity-45 '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'submit') }}"
            @if ($confirm) data-confirm="{{ $confirm }}" @endif
            {{ $attributes->except('type')->merge(['class' => $base]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
