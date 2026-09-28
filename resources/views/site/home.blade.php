@extends('site.layout')

@php
    $mc = config('midland');
    $services = config('catering_services');
    $menu = config('menu');
@endphp

@section('title', 'Traditional Asian Event Catering')

@section('content')
    {{-- ============================ HERO ============================ --}}
    <section data-hero class="grain relative isolate flex min-h-[100svh] flex-col justify-end overflow-hidden bg-ink"
             aria-label="Midland Catering — Birmingham event catering">
        <div data-bg class="absolute inset-0 -z-10" style="clip-path: inset(38% 0% 38% 0%)">
            <div data-bg-inner class="absolute inset-[-8%] will-change-transform">
                <x-site.img name="karahi-naan" alt="Meat masala with fresh coriander served alongside charred naan"
                            sizes="100vw" priority class="h-full w-full object-cover object-center" />
            </div>
            <div data-scrim aria-hidden="true" class="absolute inset-0 bg-[radial-gradient(120%_95%_at_78%_18%,transparent_0%,rgba(3,8,28,0.5)_48%,rgba(3,8,28,0.94)_100%)]"></div>
            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink via-ink/58 to-ink/28"></div>
            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-r from-ink/88 via-ink/30 to-transparent"></div>
        </div>

        <div class="container-wide relative z-10 pt-28 pb-5 sm:pt-32 lg:pt-[max(7.5rem,14vh)] lg:pb-3">
            <div data-hero-content class="grid items-end gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center lg:gap-10">
                <div class="max-w-4xl">
                    <span data-eyebrow class="line-mask mb-7 inline-block">
                        <span class="eyebrow text-gold">
                            <span aria-hidden="true" class="mr-3.5 inline-block h-px w-10 -translate-y-[0.32em] bg-gold/70"></span>{{ $mc['speciality'] }}
                        </span>
                    </span>

                    <h1 class="display-hero text-cream">
                        <span data-line class="line-mask"><span>Events with</span></span>
                        <span data-line class="line-mask"><span>a <em class="text-gold-sheen font-normal not-italic">better</em></span></span>
                        <span data-line class="line-mask"><span>taste.</span></span>
                    </h1>

                    <div data-rule aria-hidden="true" class="mt-6 h-px w-full max-w-md origin-left bg-gradient-to-r from-gold via-gold/45 to-transparent"></div>

                    <p data-copy class="body-lg mt-5 max-w-xl text-cream/72 lg:mt-6">
                        Halal catering from a working Birmingham kitchen. Weddings, functions,
                        funerals, Khatam Shareef and corporate events — cooked fresh on the day
                        for {{ $mc['guests']['min'] }} to {{ number_format($mc['guests']['max']) }} guests,
                        with staff, crockery and serving dishes if you need them.
                    </p>

                    <div data-cta class="mt-7 flex flex-wrap items-center gap-3.5 lg:mt-8">
                        <a href="{{ route('site.contact') }}"
                           class="group/btn relative inline-flex h-14 items-center gap-3 overflow-hidden rounded-full bg-gold px-9 text-[0.74rem] font-bold tracking-[0.2em] text-ink uppercase">
                            <span class="relative">Request a Quote</span>
                            <svg viewBox="0 0 24 24" class="relative size-3.5 transition-transform duration-500 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </a>
                        <a href="{{ route('site.menu') }}"
                           class="inline-flex h-14 items-center rounded-full border border-cream/25 px-9 text-[0.74rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:text-gold">
                            Explore the Menu
                        </a>
                        <a href="{{ $mc['phone_href'] }}" class="group/ph ml-1 hidden items-center gap-3 text-cream/70 transition-colors hover:text-gold sm:flex">
                            <span class="grid size-11 place-items-center rounded-full border border-cream/20 transition-colors duration-500 group-hover/ph:border-gold">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1z" />
                                </svg>
                            </span>
                            <span class="text-left leading-tight">
                                <span class="block text-[0.6rem] font-semibold tracking-[0.2em] text-cream/58 uppercase">Speak to us</span>
                                <span class="font-display text-lg">{{ $mc['phone'] }}</span>
                            </span>
                        </a>
                    </div>

                    <ul data-tags class="mt-6 flex flex-wrap gap-2 lg:mt-7" aria-label="Occasions we cater">
                        @foreach (['Weddings', 'Functions', 'Funerals', 'Corporate'] as $tag)
                            <li class="rounded-full border border-cream/18 bg-ink/25 px-4 py-2 text-[0.66rem] font-semibold tracking-[0.2em] text-cream/65 uppercase backdrop-blur-sm">{{ $tag }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="hidden justify-end lg:flex">
                    <figure data-inset class="relative w-[min(23rem,30vh)] max-w-full will-change-transform xl:w-[min(26rem,34vh)]">
                        <div class="relative aspect-[3/4] overflow-hidden">
                            <x-site.img name="biryani-claypot" alt="Chicken biryani served in a terracotta handi on a deep red cloth"
                                        sizes="(min-width: 1280px) 26rem, (min-width: 1024px) 23rem, 0px" priority
                                        class="h-full w-full object-cover" />
                            <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink/60 to-transparent"></span>
                            <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/45"></span>
                        </div>
                        <figcaption class="mt-4 flex items-center gap-3 text-[0.66rem] tracking-[0.2em] text-cream/58 uppercase">
                            <span class="size-1 rotate-45 bg-gold"></span>
                            Biryani, sealed and steamed
                        </figcaption>
                    </figure>
                </div>
            </div>

            <div class="mt-7 flex items-end justify-end gap-6 lg:mt-8">
                <span class="hidden text-right text-[0.66rem] leading-relaxed tracking-[0.2em] text-cream/58 uppercase sm:block">
                    {{ $mc['address']['line2'] }}, {{ $mc['address']['city'] }}<br>
                    <span class="text-gold/70">{{ $mc['address']['postcode'] }}</span>
                </span>
            </div>
        </div>

        <div data-marquee class="relative z-10 border-t border-cream/10 bg-ink/55 py-4 backdrop-blur-md">
            <x-site.marquee :items="collect($services)->pluck('title')->push('Halal')->all()" :speed="52" />
        </div>
    </section>

    {{-- ========================== STATEMENT ========================== --}}
    <section id="story" class="relative isolate overflow-hidden bg-ink py-24 sm:py-32 lg:py-44">
        <div class="container-x relative">
            <div class="grid gap-16 lg:grid-cols-[1fr_0.86fr] lg:gap-20">
                <div>
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Who we are
                    </span>

                    <p data-statement class="font-display mt-8 text-[clamp(1.7rem,3.5vw,3.15rem)] leading-[1.14] font-light tracking-[-0.025em] text-cream">
                        @foreach (str('We cook the food our families cook, at the scale your event needs, with the timing a professional kitchen demands.')->explode(' ') as $word)
                            <span data-word class="inline-block">{{ $word }}&nbsp;</span>
                        @endforeach
                    </p>

                    <div data-reveal class="reveal mt-10 max-w-lg space-y-5 text-cream/60" style="--reveal-from: translate3d(0, 2.75rem, 0); --reveal-blur: 0px; transition-duration:.95s">
                        <p>Midland Catering runs out of a commercial kitchen on Landor Street in Saltley. Everything is cooked here — no outsourcing, no reheated trays bought in from somewhere else.</p>
                        <p>What that gets you is control: over spice levels, over portion sizes, over what time the food actually reaches your guests.</p>
                    </div>

                    <dl class="mt-14 grid grid-cols-3 gap-6 border-t border-cream/12 pt-9">
                        @foreach ([[43, '+', 'Years in the trade'], [2000, '', 'Guests at full stretch'], [100, '%', 'Halal']] as [$value, $suffix, $label])
                            <div>
                                <dt class="sr-only">{{ $label }}</dt>
                                <dd>
                                    <span data-count-to="{{ $value }}" data-count-suffix="{{ $suffix }}" class="font-display block text-[clamp(2rem,3.6vw,3.1rem)] leading-none font-light text-gold">0{{ $suffix }}</span>
                                    <span class="mt-3 flex items-start gap-2 text-[0.68rem] leading-snug tracking-[0.2em] text-cream/58 uppercase">
                                        <span aria-hidden="true" class="mt-1.5 inline-block size-1.5 shrink-0 rotate-45 bg-gold"></span>{{ $label }}
                                    </span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="relative">
                    <figure data-mask-image class="relative aspect-[4/5] w-full overflow-hidden bg-navy" style="clip-path: inset(100% 0% 0% 0%)">
                        <div data-mask-inner class="h-full w-full">
                            <x-site.img name="midland-premises" alt="Midland Catering on Landor Street, Saltley — blue and gold signage above the loading bay"
                                        sizes="(min-width: 1024px) 42vw, 90vw" class="h-full w-full object-cover" />
                        </div>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/45"></span>
                    </figure>
                    <p class="mt-4 text-[0.78rem] tracking-[0.2em] text-cream/58 uppercase">Landor Street, Saltley — our own kitchen</p>
                </div>
            </div>
        </div>
    </section>

    @include('site.partials.story')

    @include('site.partials.journey')

    {{-- ======================== MARQUEE BAND ========================= --}}
    <div class="relative isolate overflow-hidden border-y border-cream/10 bg-ink py-8 sm:py-11">
        <x-site.marquee :items="['Cooked fresh', 'Delivered hot', 'Served on time']" :speed="58"
                        item-class="font-display text-[clamp(2rem,6.4vw,5.5rem)] font-light tracking-[-0.035em] leading-none text-cream/80" />
        <div class="mt-5 sm:mt-7">
            <x-site.marquee :items="[$mc['phone'], 'Landor Street, Saltley', 'Halal', 'Birmingham B8 1AG']" :speed="40" :direction="1" />
        </div>
    </div>

    {{-- =========================== MENU ============================= --}}
    {{-- Spotlight: the index on the left drives one large frame on the right.
         Hover or focus a course and the image, blurb and signature dishes swap. --}}
    <section data-spotlight class="grain relative isolate overflow-hidden bg-char py-24 sm:py-32" aria-label="Menu preview">
        <span data-parallax="16" aria-hidden="true"
              class="font-display pointer-events-none absolute bottom-8 left-[-4vw] text-[clamp(5rem,15vw,14rem)] leading-none whitespace-nowrap select-none text-transparent"
              style="-webkit-text-stroke: 1px color-mix(in oklab, var(--color-gold) 16%, transparent)">THE MENU</span>

        <div class="container-wide relative">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> The food
                    </span>
                    <h2 data-split class="display-xl mt-6 text-cream" style="visibility:hidden">A menu built for the room.</h2>
                </div>
                <div class="max-w-sm">
                    <p class="text-cream/58">Our full menu, cooked to order in our own kitchen. Build your own selection &mdash; quantities and spice levels are set per event.</p>
                    <p class="mt-3 text-[0.7rem] tracking-[0.12em] text-cream/58 uppercase">
                        {{ collect($menu)->sum(fn ($c) => count($c['dishes'])) }} dishes &middot; {{ $mc['guests']['label'] }}
                    </p>
                </div>
            </div>

            <div class="mt-14 grid gap-10 lg:mt-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-start lg:gap-16">
                {{-- Index --}}
                <ul class="border-t border-cream/12">
                    @foreach ($menu as $cat)
                        <li class="border-b border-cream/12">
                            <a href="{{ route('site.menu') }}#cat-{{ $cat['id'] }}"
                               data-spotlight-item data-index="{{ $loop->index }}"
                               aria-describedby="spot-{{ $cat['id'] }}"
                               class="group/s relative flex items-center gap-5 py-6 sm:gap-7 sm:py-7">
                                <span aria-hidden="true" data-spot-fill
                                      class="absolute inset-x-[-1rem] inset-y-0 origin-left scale-x-0 bg-gold/8"></span>

                                <span class="font-display relative w-9 shrink-0 text-base leading-none font-light text-gold/70 tabular-nums transition-colors duration-500 group-hover/s:text-gold">{{ $cat['n'] }}</span>

                                <span class="relative min-w-0 flex-1">
                                    <span class="font-display block text-[clamp(1.5rem,3.1vw,2.6rem)] leading-[1.05] font-light tracking-[-0.025em] text-cream transition-colors duration-500 group-hover/s:text-gold">{{ $cat['title'] }}</span>
                                    <span id="spot-{{ $cat['id'] }}" class="mt-1.5 block truncate text-[0.78rem] text-cream/58">{{ $cat['sub'] }}</span>
                                </span>

                                {{-- Thumbnail rides along on mobile, where there is no big frame --}}
                                <span aria-hidden="true" class="relative size-14 shrink-0 overflow-hidden bg-ink lg:hidden">
                                    <x-site.img :name="$cat['image']" sizes="3.5rem" class="h-full w-full object-cover" />
                                </span>

                                <span class="relative hidden shrink-0 items-center gap-4 lg:flex">
                                    <span class="text-[0.66rem] font-semibold tracking-[0.2em] text-cream/45 uppercase tabular-nums">{{ count($cat['dishes']) }}</span>
                                    <span aria-hidden="true" class="grid size-10 place-items-center rounded-full border border-cream/18 text-cream/55 transition-all duration-500 group-hover/s:border-gold group-hover/s:bg-gold group-hover/s:text-ink">
                                        <svg viewBox="0 0 24 24" class="size-3.5 transition-transform duration-500 group-hover/s:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Frame --}}
                <div class="sticky top-28 hidden lg:block" aria-hidden="true">
                    <div class="relative aspect-[4/5] w-full overflow-hidden bg-ink">
                        @foreach ($menu as $cat)
                            <figure data-spotlight-panel data-index="{{ $loop->index }}"
                                    class="absolute inset-0"
                                    style="opacity: {{ $loop->first ? 1 : 0 }}; clip-path: inset({{ $loop->first ? '0%' : '0% 0% 100% 0%' }})">
                                <x-site.img :name="$cat['image']" sizes="(min-width: 1024px) 45vw, 0px"
                                            class="h-full w-full scale-[1.03] object-cover" />
                                <span class="absolute inset-0 bg-gradient-to-t from-char via-char/68 to-char/10"></span>
                                <figcaption class="absolute inset-x-0 bottom-0 p-8">
                                    <span class="text-[0.62rem] font-bold tracking-[0.2em] text-gold uppercase">{{ $cat['n'] }} &mdash; {{ $cat['title'] }}</span>
                                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-cream/72">{{ $cat['blurb'] }}</p>
                                    <ul class="mt-5 flex flex-wrap gap-2">
                                        @foreach (collect($cat['dishes'])->where('signature', true)->take(3) as $dish)
                                            <li class="rounded-full border border-cream/22 bg-ink/35 px-3.5 py-1.5 text-[0.64rem] font-semibold tracking-[0.12em] text-cream/80 backdrop-blur-sm">{{ $dish['name'] }}</li>
                                        @endforeach
                                    </ul>
                                </figcaption>
                            </figure>
                        @endforeach
                        <span class="pointer-events-none absolute inset-4 border border-gold/40"></span>
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <a href="{{ route('site.menu') }}"
                   class="inline-flex h-12 items-center gap-3 rounded-full border border-gold/40 px-7 text-[0.7rem] font-bold tracking-[0.2em] text-gold uppercase transition-colors hover:bg-gold hover:text-ink">
                    Full menu
                    <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>
        </div>
    </section>

    @include('site.partials.process')

    @include('site.partials.collage')

    @include('site.partials.reviews')
    @include('site.partials.map')
    @include('site.partials.cta')
@endsection
