{{-- The plain top of a policy page: no hero image, just what it is and when it last changed. --}}
<section class="relative isolate overflow-hidden bg-ink pt-36 pb-12 sm:pt-44 sm:pb-16">
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.04]"></div>
    <div class="container-x relative">
        <div class="max-w-3xl">
        <span class="eyebrow flex items-center gap-3.5 text-gold">
            <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> {{ $eyebrow }}
        </span>
        <h1 class="font-display mt-6 text-[clamp(2.4rem,6vw,4.25rem)] leading-[1.02] font-light tracking-[-0.03em] text-cream">{{ $heading }}</h1>
        <p class="mt-6 text-sm text-cream/60">Last updated {{ $updated }}</p>
        </div>
    </div>
</section>
