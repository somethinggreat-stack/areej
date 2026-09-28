@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'neutral' => 'bg-surface-3 text-text-muted',
        'good'    => 'bg-good-bg text-good',
        'warn'    => 'bg-warn-bg text-warn',
        'bad'     => 'bg-bad-bg text-bad',
        'info'    => 'bg-info-bg text-info',
        'gold'    => 'bg-gold/15 text-gold-ink dark:bg-gold/20 dark:text-gold-lit',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[0.7rem] font-semibold whitespace-nowrap ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    @if ($dot)
        <span aria-hidden="true" class="size-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>
