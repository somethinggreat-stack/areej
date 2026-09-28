@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'href' => null,
    'icon' => null,
])

@php
    $tones = [
        'neutral' => 'text-text',
        'good'    => 'text-good',
        'warn'    => 'text-warn',
        'bad'     => 'text-bad',
        'info'    => 'text-info',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card block p-5 transition-colors ' . ($href ? 'hover:border-line-strong hover:bg-surface-2' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="label-sm">{{ $label }}</p>
        @if ($icon)
            <x-icon :name="$icon" class="size-4 shrink-0 text-text-faint" />
        @endif
    </div>

    <p class="mt-2 text-3xl font-semibold tabular-nums {{ $tones[$tone] ?? $tones['neutral'] }}">{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-xs text-text-faint">{{ $hint }}</p>
    @endif

    @if ($href)
        <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-link">
            {{ __('View') }}
            <x-icon name="arrow-right" class="size-3 rtl:rotate-180" />
        </span>
    @endif
</{{ $tag }}>
