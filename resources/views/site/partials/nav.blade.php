@php
    $mc = config('midland');
    $links = [
        ['route' => 'site.home', 'label' => 'Home', 'index' => '01'],
        ['route' => 'site.services', 'label' => 'Services', 'index' => '02'],
        ['route' => 'site.menu', 'label' => 'Our Menu', 'index' => '03'],
        ['route' => 'site.gallery', 'label' => 'Gallery', 'index' => '04'],
        ['route' => 'site.about', 'label' => 'Our Story', 'index' => '05'],
        ['route' => 'site.contact', 'label' => 'Contact', 'index' => '06'],
    ];
@endphp

<div data-nav-panel id="site-menu" class="pointer-events-none fixed inset-0 z-[100] opacity-0" role="dialog" aria-modal="true" aria-label="Site navigation">
    <div data-sheet data-lenis-prevent class="grain relative flex h-[100svh] w-full flex-col overflow-y-auto overscroll-contain bg-navy">
        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-1/3 left-1/2 h-[70vh] w-[70vw] -translate-x-1/2 rounded-full bg-royal/25 blur-[140px]"></div>

        <div class="relative z-10 flex shrink-0 items-center justify-between px-(--spacing-gutter) py-6">
            <a href="{{ route('site.home') }}" data-nav-close class="flex items-center gap-3">
                <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="40" height="40" class="h-10 w-10 object-contain">
                <span class="eyebrow text-cream/58">Midland Catering</span>
            </a>

            <button type="button" data-nav-close aria-label="Close menu"
                    class="group/close flex items-center gap-3 text-[0.68rem] font-semibold tracking-[0.2em] text-cream/70 uppercase transition-colors hover:text-gold">
                <span class="hidden sm:inline">Close</span>
                <span class="grid size-11 place-items-center rounded-full border border-cream/20 transition-colors duration-500 group-hover/close:border-gold">
                    <svg viewBox="0 0 24 24" class="size-4 transition-transform duration-500 group-hover/close:rotate-90" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </span>
            </button>
        </div>

        <nav class="relative z-10 flex flex-1 items-center px-(--spacing-gutter) py-8">
            <ul class="w-full max-w-4xl">
                @foreach ($links as $link)
                    @php
    $active = request()->routeIs($link['route']);
@endphp
                    <li class="relative">
                        <span aria-hidden="true" class="block h-px bg-cream/12"></span>
                        <a href="{{ route($link['route']) }}" data-nav-close
                           @if ($active) aria-current="page" @endif
                           class="group/nav flex items-baseline gap-5 py-[clamp(0.5rem,1.4vh,1.05rem)] sm:gap-8">
                            <span class="w-7 shrink-0 font-sans text-[0.6rem] font-semibold tracking-[0.2em] text-gold/60 tabular-nums">{{ $link['index'] }}</span>
                            <span data-nav-item class="line-mask flex-1">
                                <span class="font-display block text-[clamp(2.15rem,7.2vw,5.25rem)] leading-[1.02] font-light tracking-[-0.035em] transition-colors duration-500 {{ $active ? 'text-gold' : 'text-cream group-hover/nav:text-gold' }}">
                                    {{ $link['label'] }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
                <li><span aria-hidden="true" class="block h-px bg-cream/12"></span></li>
            </ul>
        </nav>

        {{-- On phones only the phone number, email and quote button are shown, so the
             whole panel fits one screen; address and hours live on the contact page. --}}
        <div data-nav-aside class="relative z-10 grid shrink-0 grid-cols-[1fr_auto] items-end gap-x-4 gap-y-6 border-t border-cream/10 px-(--spacing-gutter) pt-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:grid-cols-2 sm:items-start sm:gap-8 sm:py-8 lg:grid-cols-4">
            <div>
                <p class="eyebrow mb-3 text-gold/70">Call the kitchen</p>
                <a href="{{ $mc['phone_href'] }}" class="link-underline font-display text-xl text-cream">{{ $mc['phone'] }}</a>
            </div>
            <a href="{{ route('site.contact') }}" data-nav-close
               class="inline-flex h-11 items-center rounded-full bg-gold px-5 text-[0.62rem] font-bold tracking-[0.18em] whitespace-nowrap text-ink uppercase transition-colors hover:bg-gold-lit sm:hidden">
                Get a Quote
            </a>
            <div class="col-span-2 sm:col-span-1">
                <p class="eyebrow mb-3 text-gold/70">Email</p>
                <a href="{{ $mc['email_href'] }}" class="link-underline text-sm break-all text-cream/80">{{ $mc['email'] }}</a>
            </div>
            <div class="hidden sm:block">
                <p class="eyebrow mb-3 text-gold/70">Find us</p>
                <p class="text-sm leading-relaxed text-cream/70">
                    {{ $mc['address']['line1'] }}<br>
                    {{ $mc['address']['line2'] }}, {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}
                </p>
            </div>
            <div class="hidden flex-col items-start gap-3 sm:flex">
                <p class="eyebrow text-gold/70">Open</p>
                <p class="text-sm text-cream/70">{{ $mc['contact_hours'] }}</p>
                <a href="{{ route('site.contact') }}" data-nav-close
                   class="group/btn mt-2 inline-flex h-11 items-center gap-2.5 rounded-full bg-gold px-6 text-[0.68rem] font-bold tracking-[0.2em] text-ink uppercase transition-colors hover:bg-gold-lit">
                    Request a Quote
                </a>
            </div>
        </div>
    </div>
</div>
