{{--
    First-visit opening sequence, once per session. Removed from the DOM once complete.

    A jali-inspired eight-point star draws itself around the emblem, the wordmark
    rises in letter by letter, and a golden iris opens from the centre to reveal the
    page. The veil is masked by --iris (see .loader-veil) so the hole shows the real
    hero underneath, already animating in.
--}}
@php
    // Dial ticks around the star: every fourth one longer, like a watch bezel.
    $ticks = collect(range(0, 47))->map(function (int $i): array {
        $angle = deg2rad($i * 7.5);
        $inner = $i % 4 === 0 ? 164 : 172;

        return [
            'x1' => round(sin($angle) * $inner, 2),
            'y1' => round(-cos($angle) * $inner, 2),
            'x2' => round(sin($angle) * 182, 2),
            'y2' => round(-cos($angle) * 182, 2),
        ];
    });
@endphp
<noscript><style>[data-loader]{display:none!important}</style></noscript>

<div data-loader class="fixed inset-0 z-[120]" role="status" aria-live="polite" aria-label="Loading Midland Catering">
    <div data-veil class="loader-veil absolute inset-0 bg-ink">
        <div class="grain h-full w-full overflow-hidden">
            {{-- Deep royal bloom behind the emblem --}}
            <div data-bloom aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-0"
                 style="background: radial-gradient(circle at 50% 46%, color-mix(in oklab, var(--color-royal) 38%, transparent) 0%, color-mix(in oklab, var(--color-navy) 70%, transparent) 38%, transparent 70%)"></div>
            <div class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.035]"></div>

            {{-- Cinematic frame: corner brackets and edge labels --}}
            <div data-frame aria-hidden="true" class="pointer-events-none absolute inset-4 sm:inset-7">
                <span class="absolute top-0 left-0 h-5 w-5 border-t border-l border-gold/60"></span>
                <span class="absolute top-0 right-0 h-5 w-5 border-t border-r border-gold/60"></span>
                <span class="absolute bottom-0 left-0 h-5 w-5 border-b border-l border-gold/60"></span>
                <span class="absolute right-0 bottom-0 h-5 w-5 border-r border-b border-gold/60"></span>

                <span class="eyebrow absolute top-1 left-9 hidden text-cream/45 sm:block">Midland Catering</span>
                <span class="eyebrow absolute top-1 right-9 hidden text-cream/45 sm:block">Birmingham</span>
            </div>

            <div class="absolute inset-0 flex flex-col items-center justify-center px-6">
                {{-- The emblem inside its drawn star --}}
                <div data-emblem class="relative grid h-[min(52vmin,25rem)] w-[min(52vmin,25rem)] place-items-center">
                    <svg viewBox="-200 -200 400 400" fill="none" aria-hidden="true"
                         class="pointer-events-none absolute inset-0 h-full w-full overflow-visible text-gold">
                        <g data-dial>
                            <circle data-draw r="190" stroke="currentColor" stroke-width="1" stroke-opacity="0.75" />
                            @foreach ($ticks as $tick)
                                <line data-tick x1="{{ $tick['x1'] }}" y1="{{ $tick['y1'] }}" x2="{{ $tick['x2'] }}" y2="{{ $tick['y2'] }}"
                                      stroke="currentColor" stroke-width="1" stroke-opacity="0.55" />
                            @endforeach
                        </g>
                        <g data-star>
                            <polygon data-draw points="0,-152 152,0 0,152 -152,0" stroke="currentColor" stroke-width="1" stroke-opacity="0.9" />
                            <polygon data-draw points="107.5,-107.5 107.5,107.5 -107.5,107.5 -107.5,-107.5" stroke="currentColor" stroke-width="1" stroke-opacity="0.9" />
                            <circle data-draw r="152" stroke="currentColor" stroke-width="0.6" stroke-opacity="0.35" stroke-dasharray="2 6" />
                            <circle data-draw r="104" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.85" />
                        </g>
                    </svg>

                    <div data-glow aria-hidden="true" class="pointer-events-none absolute h-[46%] w-[46%] rounded-full opacity-0"
                         style="box-shadow: 0 0 60px 12px color-mix(in oklab, var(--color-gold) 38%, transparent), 0 0 140px 40px color-mix(in oklab, var(--color-royal) 45%, transparent)"></div>

                    <img data-logo src="{{ asset('img/midland-logo.webp') }}" alt="" width="200" height="200"
                         class="relative h-[46%] w-[46%] object-contain opacity-0">
                </div>

                {{-- Wordmark --}}
                <p data-word class="font-display mt-[clamp(1.25rem,4vh,2.5rem)] text-center text-[clamp(1.3rem,5.4vw,3.1rem)] leading-none font-light tracking-[0.14em] whitespace-nowrap">
                    <span class="text-cream">MIDLAND</span>
                    <span class="text-gold">CATERING</span>
                </p>

                <div data-rule class="mt-5 h-px w-[min(18rem,64vw)] origin-center bg-gradient-to-r from-transparent via-gold/70 to-transparent"></div>

                <span class="mt-4 block overflow-hidden">
                    <span data-sub class="eyebrow block text-center text-cream/60">Halal Asian Event Catering</span>
                </span>
            </div>

            {{-- Counter and progress along the bottom edge --}}
            <div data-meter aria-hidden="true" class="absolute inset-x-8 bottom-8 sm:inset-x-14 sm:bottom-12">
                <div class="flex items-end justify-between gap-6 px-1 pb-3">
                    <span class="font-display text-[clamp(2.25rem,6vw,4.5rem)] leading-none font-light text-cream tabular-nums"><span data-count>000</span></span>
                    <span class="eyebrow pb-1 text-right text-cream/45">Preparing the feast</span>
                </div>
                <div class="h-px w-full bg-cream/12">
                    <div data-progress class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-gold-deep via-gold to-gold-lit"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- The golden edge that rides the iris as it opens --}}
    <div data-iris-ring aria-hidden="true"
         class="pointer-events-none absolute top-1/2 left-1/2 rounded-full border-2 border-gold/90 opacity-0"
         style="box-shadow: 0 0 36px 4px color-mix(in oklab, var(--color-gold) 45%, transparent), inset 0 0 36px 4px color-mix(in oklab, var(--color-gold) 30%, transparent)"></div>
</div>
