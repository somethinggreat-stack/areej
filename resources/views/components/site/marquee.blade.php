@props([
    'items' => [],
    'speed' => 40,
    'direction' => -1,
    'itemClass' => 'text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-cream/58',
    'copies' => 4,
])

{{-- Seamless loop: several identical copies wrapped by GSAP so there is never
     a visible jump, however wide the screen gets. --}}
<div data-marquee-track data-speed="{{ $speed }}" data-direction="{{ $direction }}"
     class="mask-edges relative flex w-full overflow-hidden"
     role="marquee" aria-label="{{ implode(', ', $items) }}">
    @for ($c = 0; $c < $copies; $c++)
        <div data-copy class="flex shrink-0 items-center will-change-transform" @if ($c > 0) aria-hidden="true" @endif>
            @foreach ($items as $item)
                <span class="flex items-center {{ $itemClass }}">
                    <span class="whitespace-nowrap">{{ $item }}</span>
                    <span aria-hidden="true" class="mx-[0.32em] inline-block translate-y-[-0.08em] text-[0.42em] text-gold">✦</span>
                </span>
            @endforeach
        </div>
    @endfor
</div>
