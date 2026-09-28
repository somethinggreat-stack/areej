{{-- First-visit branded opening sequence. Removed from the DOM once complete. --}}
<div data-loader class="fixed inset-0 z-[120] flex items-center justify-center" role="status" aria-live="polite" aria-label="Loading Midland Catering">
    <div data-panel="top" class="absolute inset-x-0 top-0 h-1/2 bg-navy will-change-transform">
        <div class="brand-pattern absolute inset-0 opacity-[0.05]"></div>
    </div>
    <div data-panel="bottom" class="absolute inset-x-0 bottom-0 h-1/2 bg-navy will-change-transform">
        <div class="brand-pattern absolute inset-0 opacity-[0.05]"></div>
    </div>

    <div data-seam class="absolute inset-x-0 top-1/2 h-px origin-center bg-gradient-to-r from-transparent via-gold to-transparent"></div>

    <div data-mark class="relative z-10 flex flex-col items-center px-6 text-center">
        <span class="relative grid place-items-center">
            <span data-logo class="block opacity-0">
                <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="132" height="132"
                     class="h-24 w-24 object-contain sm:h-[8.25rem] sm:w-[8.25rem]">
            </span>
            <svg data-ring viewBox="0 0 120 120" fill="none" aria-hidden="true"
                 class="pointer-events-none absolute inset-[-14%] text-gold">
                <circle cx="60" cy="60" r="57" stroke="currentColor" stroke-width="0.7" stroke-opacity="0.7" vector-effect="non-scaling-stroke" />
            </svg>
        </span>

        <div data-word class="mt-8 flex flex-col items-center">
            <span class="line-mask">
                <span class="font-display block text-[clamp(2rem,7vw,4.25rem)] leading-[0.9] font-light tracking-[-0.03em] text-cream">MIDLAND</span>
            </span>
            <span class="line-mask">
                <span class="font-display block text-[clamp(2rem,7vw,4.25rem)] leading-[0.9] font-light tracking-[-0.03em] text-gold">CATERING</span>
            </span>
        </div>

        <div data-rule class="mt-7 h-px w-[min(20rem,72vw)] origin-center bg-gradient-to-r from-transparent via-gold/70 to-transparent"></div>

        <span class="mt-5 block overflow-hidden">
            <span data-sub class="eyebrow block text-cream/58">Birmingham · Events · Exceptional Food</span>
        </span>
    </div>
</div>
