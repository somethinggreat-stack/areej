{{-- Full-screen lightbox. Arrows move, Home/End jump, Escape closes, Tab is
     trapped inside, and touch swipes. Focus returns to the tile that opened it. --}}
<div data-lightbox hidden role="dialog" aria-modal="true" aria-label="Gallery viewer"
     class="fixed inset-0 z-[130] flex flex-col opacity-0">

    <button type="button" data-lightbox-close tabindex="-1" aria-label="Close gallery"
            class="absolute inset-0 cursor-default bg-ink/92 backdrop-blur-xl"></button>

    <div class="relative z-10 flex items-center justify-between gap-4 px-(--spacing-gutter) py-5">
        <span class="text-[0.68rem] font-semibold tracking-[0.2em] text-cream/58 uppercase tabular-nums">
            <span data-lightbox-position class="text-gold">01</span>
            <span class="mx-2 text-cream/25">/</span>
            <span data-lightbox-total>00</span>
        </span>

        <button type="button" data-lightbox-close
                class="group/x flex items-center gap-3 text-[0.66rem] font-semibold tracking-[0.2em] text-cream/70 uppercase transition-colors hover:text-gold">
            <span class="hidden sm:inline">Close</span>
            <span class="grid size-11 place-items-center rounded-full border border-cream/22 transition-colors duration-500 group-hover/x:border-gold">
                <svg viewBox="0 0 24 24" class="size-4 transition-transform duration-500 group-hover/x:rotate-90" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </span>
        </button>
    </div>

    <div class="relative z-10 flex min-h-0 flex-1 items-center justify-center px-4 sm:px-(--spacing-gutter)">
        <button type="button" data-lightbox-prev aria-label="Previous image"
                class="group/n absolute top-1/2 left-3 z-20 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-cream/20 text-cream/70 transition-colors duration-500 hover:border-gold hover:bg-gold hover:text-ink sm:grid">
            <svg viewBox="0 0 24 24" class="size-4 rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
        </button>

        <figure class="relative flex h-full max-h-full w-full max-w-6xl flex-col items-center justify-center">
            <div class="relative flex max-h-[74svh] w-full flex-1 items-center justify-center">
                <img data-lightbox-image src="" alt="" class="max-h-[74svh] w-auto max-w-full object-contain">
            </div>
            <figcaption class="mt-5 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center">
                <span data-lightbox-category class="text-[0.62rem] font-bold tracking-[0.2em] text-gold uppercase"></span>
                <span data-lightbox-caption class="text-sm text-cream/65"></span>
            </figcaption>
        </figure>

        <button type="button" data-lightbox-next aria-label="Next image"
                class="group/n absolute top-1/2 right-3 z-20 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-cream/20 text-cream/70 transition-colors duration-500 hover:border-gold hover:bg-gold hover:text-ink sm:grid">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
        </button>
    </div>

    <div data-lightbox-strip class="no-scrollbar relative z-10 flex justify-start gap-2 overflow-x-auto px-(--spacing-gutter) py-5 sm:justify-center"></div>
</div>
