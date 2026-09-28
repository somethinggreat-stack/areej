@php
    $mc = config('midland');
@endphp

<header data-header class="pointer-events-none fixed inset-x-0 top-0 z-[90] will-change-transform">
    <div data-plate aria-hidden="true" class="absolute inset-0 bg-ink/72 opacity-0 backdrop-blur-xl backdrop-saturate-150"></div>
    <div aria-hidden="true" class="rule-gold absolute inset-x-0 bottom-0"></div>

    <div class="container-wide pointer-events-auto flex items-center justify-between gap-4 py-5">
        {{-- Direct contact, desktop only --}}
        <div class="hidden items-center gap-1 lg:flex">
            <a href="{{ $mc['phone_href'] }}" class="group/ic flex items-center gap-2.5 rounded-full px-3 py-2 text-cream/70 transition-colors hover:text-gold">
                <span class="grid size-9 place-items-center rounded-full border border-cream/15 transition-colors group-hover/ic:border-gold/60">
                    <svg viewBox="0 0 24 24" class="size-[15px]" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1z" />
                    </svg>
                </span>
                <span class="hidden text-[0.68rem] font-semibold tracking-[0.2em] uppercase xl:inline">{{ $mc['phone'] }}</span>
            </a>
        </div>

        {{-- Wordmark --}}
        <a href="{{ route('site.home') }}" class="group/logo flex shrink-0 items-center gap-2.5 sm:gap-3 lg:absolute lg:left-1/2 lg:-translate-x-1/2">
            <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="44" height="44" class="h-10 w-10 shrink-0 object-contain sm:h-11 sm:w-11">
            <span class="flex flex-col leading-none">
                <span class="font-display text-[0.95rem] leading-none font-light tracking-[0.02em] text-cream sm:text-[1.05rem]">
                    Midland<span class="ml-1.5 text-gold">Catering</span>
                </span>
                <span class="mt-[0.3rem] hidden text-[0.55rem] leading-none font-semibold tracking-[0.2em] text-cream/68 uppercase xs:block">Birmingham</span>
            </span>
        </a>

        {{-- CTA + menu --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <span class="hidden sm:block">
                <a href="{{ route('site.contact') }}"
                   class="group/btn relative inline-flex h-11 items-center gap-2.5 overflow-hidden rounded-full border border-gold/45 px-6 text-[0.66rem] font-bold tracking-[0.2em] text-gold uppercase transition-colors duration-500 hover:text-ink">
                    <span aria-hidden="true" class="absolute inset-0 origin-bottom scale-y-0 bg-gold transition-transform duration-[600ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover/btn:scale-y-100"></span>
                    <span class="relative">Request a Quote</span>
                </a>
            </span>

            <button type="button" data-nav-toggle aria-expanded="false" aria-controls="site-menu" aria-label="Open menu"
                    class="group/menu flex items-center gap-3 rounded-full border border-cream/18 p-2 transition-colors duration-500 hover:border-gold/60 xs:pl-5 min-[420px]:pr-2">
                <span class="hidden text-[0.66rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors group-hover/menu:text-gold xs:inline">Menu</span>
                <span class="relative grid size-9 place-items-center rounded-full bg-gold text-ink">
                    <span class="flex flex-col gap-[3px]">
                        <span class="block h-[1.5px] w-4 bg-current"></span>
                        <span class="block h-[1.5px] w-4 bg-current"></span>
                        <span class="block h-[1.5px] w-4 bg-current"></span>
                    </span>
                </span>
            </button>
        </div>
    </div>
</header>
