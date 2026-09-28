@php
    $pieces = config('story.collage');
@endphp

{{-- Scroll-driven collage: frames fly in from different edges, at different
     rotations and depths, and settle into a deliberately off-grid composition.
     Mobile gets an editorial swipe strip rather than a cramped grid. --}}
<section data-collage class="relative isolate overflow-hidden bg-ink py-24 sm:py-32" aria-label="Recent events">
    <span data-parallax="-18" aria-hidden="true"
          class="font-display pointer-events-none absolute top-6 right-[-6vw] text-[clamp(5rem,16vw,15rem)] leading-none whitespace-nowrap select-none text-transparent"
          style="-webkit-text-stroke: 1px color-mix(in oklab, var(--color-gold) 20%, transparent)">BIRMINGHAM</span>

    <div class="container-wide relative">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <span class="eyebrow flex items-center gap-3.5 text-gold">
                    <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Recent work
                </span>
                <h2 data-split class="display-xl mt-6 text-cream" style="visibility:hidden">Rooms we have<br>fed lately.</h2>
            </div>
            <div class="flex flex-col items-start gap-6">
                <p class="max-w-xs text-cream/58">
                    Weddings, conferences, family gatherings and community events across
                    Birmingham and the West Midlands.
                </p>
                <a href="{{ route('site.gallery') }}"
                   class="inline-flex h-12 items-center gap-3 rounded-full border border-cream/25 px-7 text-[0.7rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:text-gold">
                    View gallery
                    <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>
        </div>

        {{-- Desktop collage --}}
        <div class="mt-14 hidden auto-rows-[7vw] grid-cols-13 gap-4 lg:mt-20 lg:grid">
            @foreach ($pieces as $p)
                <figure data-piece
                        data-fx="{{ $p['x'] }}" data-fy="{{ $p['y'] }}" data-fr="{{ $p['r'] }}" data-depth="{{ $p['depth'] }}"
                        class="group relative overflow-hidden will-change-transform {{ $p['class'] }}">
                    <div data-piece-img class="absolute inset-[-10%]">
                        <x-site.img :name="$p['file']" sizes="(min-width: 1024px) 35vw, 0px"
                                    class="h-full w-full object-cover transition-transform duration-[1200ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-105" />
                    </div>
                    <span aria-hidden="true" class="absolute inset-0 bg-ink/25 transition-opacity duration-700 group-hover:opacity-0"></span>
                    <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/0 transition-colors duration-700 group-hover:border-gold/45"></span>
                </figure>
            @endforeach
        </div>

        {{-- Mobile strip --}}
        <div tabindex="0" role="group" aria-label="Recent event photographs"
             class="no-scrollbar -mx-(--spacing-gutter) mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-(--spacing-gutter) outline-offset-4 lg:hidden">
            @foreach ($pieces as $p)
                <figure class="relative aspect-[3/4] w-[68vw] shrink-0 snap-center overflow-hidden">
                    <x-site.img :name="$p['file']" sizes="(max-width: 1023px) 68vw, 0px" class="h-full w-full object-cover" />
                    <span aria-hidden="true" class="absolute inset-3 border border-gold/30"></span>
                </figure>
            @endforeach
        </div>
    </div>
</section>
