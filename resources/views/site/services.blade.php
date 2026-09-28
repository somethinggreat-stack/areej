@extends('site.layout')

@php
    $mc = config('midland');
    $services = config('catering_services');
@endphp

@section('title', 'Catering Services')
@section('description', 'Wedding, party, function, funeral, Khatam Shareef, corporate and home event catering across Birmingham and the West Midlands.')

@section('content')
    <x-site.page-hero
        eyebrow="What we cater"
        :lines="['Every occasion', 'has its own', 'kind of hunger.']"
        lede="Seven occasions, one kitchen. What changes between them is scale, timing and how the food reaches the table — not the standard it is cooked to."
        image="mixed-grill-platter"
        :meta="['Halal', $mc['guests']['label'], $mc['city']]" />

    <section class="relative isolate overflow-hidden bg-ink py-24 sm:py-32">
        <div class="container-wide">
            <div class="mb-12 flex flex-col gap-6 lg:mb-16 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Our services
                    </span>
                    <h2 data-split class="display-lg mt-6 text-cream" style="visibility:hidden">Choose the shape of your day.</h2>
                </div>
                <p class="max-w-sm text-cream/58">
                    Not sure which fits? Most enquiries start with a phone call and end with a menu
                    we have written specifically for the room.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-12 lg:gap-6">
                @foreach ($services as $s)
                    @php
                        $shape = $loop->first ? 'feature' : ($loop->index === 3 ? 'wide' : 'half');
                        $span = match ($shape) {
                            'feature' => 'lg:col-span-7 lg:row-span-2 min-h-[26rem] lg:min-h-[36rem]',
                            'wide' => 'lg:col-span-12 min-h-[20rem] lg:min-h-[24rem]',
                            default => 'lg:col-span-5 min-h-[19rem] lg:min-h-[17.5rem]',
                        };
                    @endphp

                    <a href="{{ route('site.service', $s['slug']) }}" data-reveal
                       class="reveal group relative flex items-end overflow-hidden bg-navy {{ $span }}"
                       style="--reveal-from: translate3d(0, 2.75rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:{{ ($loop->index % 3) * 0.06 }}s">
                        <x-site.img :name="$s['image']" :alt="$s['title']" sizes="(min-width: 1024px) 45vw, (min-width: 640px) 50vw, 92vw"
                             class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1400ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.07]" />
                        <span aria-hidden="true" class="absolute inset-0 {{ $shape === 'wide' ? 'bg-gradient-to-r from-ink via-ink/70 to-ink/25' : 'bg-gradient-to-t from-ink via-ink/58 to-ink/10' }}"></span>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/0 transition-colors duration-[900ms] group-hover:border-gold/45"></span>

                        <div class="relative z-10 w-full p-7 sm:p-9 {{ $shape === 'wide' ? 'max-w-2xl' : '' }}">
                            <div class="flex items-baseline gap-4">
                                <span class="font-display text-2xl leading-none font-light text-gold tabular-nums sm:text-3xl">{{ $s['index'] }}</span>
                                <span class="h-px flex-1 bg-cream/22"></span>
                                <span class="text-[0.6rem] font-semibold tracking-[0.2em] text-cream/58 uppercase">{{ $s['scale'] }}</span>
                            </div>
                            <h3 class="mt-4 text-cream {{ $shape === 'feature' ? 'display-lg' : 'display-md' }}">{{ $s['title'] }}</h3>
                            <p class="mt-3 max-w-md text-sm leading-relaxed text-cream/60">
                                {{ in_array($shape, ['feature', 'wide'], true) ? $s['lede'] : $s['short'] }}
                            </p>
                            <p class="mt-4 text-[0.62rem] font-semibold tracking-[0.2em] text-gold/70 uppercase">{{ $s['detail'] }}</p>
                            <span class="mt-6 inline-flex items-center gap-3 text-[0.68rem] font-semibold tracking-[0.2em] text-cream uppercase">
                                <span class="link-underline">Explore service</span>
                                <span class="grid size-8 place-items-center rounded-full border border-cream/30 transition-colors duration-500 group-hover:border-gold group-hover:bg-gold group-hover:text-ink">
                                    <svg viewBox="0 0 24 24" class="size-3" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                                </span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @include('site.partials.cta')
@endsection
