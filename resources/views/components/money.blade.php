@props(['pence' => 0, 'tone' => null, 'sign' => false])

@php
    $amount = ((int) $pence) / 100;
    $tones = ['good' => 'text-good', 'bad' => 'text-bad', 'warn' => 'text-warn', 'muted' => 'text-text-muted'];
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums ' . ($tones[$tone] ?? '')]) }}>
    {{ $sign && $amount > 0 ? '+' : '' }}£{{ number_format($amount, 2) }}
</span>
