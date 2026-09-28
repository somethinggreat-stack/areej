@extends('site.layout')

@php
    $mc = config('midland');
    $others = collect(config('catering_services'))->where('slug', '!=', $service['slug'])->take(3);
    $words = explode(' ', $service['title']);
    $half = (int) ceil(count($words) / 2);
    $lines = array_filter([
        implode(' ', array_slice($words, 0, $half)),
        implode(' ', array_slice($words, $half)),
    ]);
@endphp

@section('title', $service['title'])
@section('description', $service['short'])

@section('content')
    <x-site.page-hero
        :eyebrow="$service['detail']"
        :lines="$lines"
        :lede="$service['lede']"
        :image="$service['image']"
        :meta="[$service['scale'], 'Halal', $mc['city']]"
        :crumb="['label' => 'All services', 'url' => route('site.services')]" />

    <section class="relative isolate overflow-hidden bg-ink py-24 sm:py-32">
        <div class="container-x grid gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-20">
            <div>
                <span class="eyebrow flex items-center gap-3.5 text-gold">
                    <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> The detail
                </span>
                <h2 data-split class="display-lg mt-6 text-cream" style="visibility:hidden">How we run it</h2>

                <div class="mt-8 space-y-6">
                    @foreach ($service['body'] as $paragraph)
                        <p data-reveal class="reveal body-lg text-cream/62"
                           style="--reveal-from: translate3d(0, 2rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:{{ $loop->index * 0.08 }}s">
                            {{ $paragraph }}
                        </p>
                    @endforeach
                </div>

                <div class="mt-10">
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Included
                    </span>
                    <ul class="mt-7">
                        @foreach ($mc['extras'] as $item)
                            <li data-reveal class="reveal flex items-start gap-4 border-b border-cream/10 py-4"
                                style="--reveal-from: translate3d(0, 1.5rem, 0); --reveal-blur: 0px; transition-duration:.8s; transition-delay:{{ $loop->index * 0.05 }}s">
                                <span aria-hidden="true" class="mt-2 inline-block size-1.5 shrink-0 rotate-45 bg-gold"></span>
                                <span class="text-cream/78">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <a href="{{ route('site.contact') }}"
                   class="mt-10 inline-flex h-13 items-center gap-3 rounded-full bg-gold px-8 py-4 text-[0.7rem] font-bold tracking-[0.2em] text-ink uppercase transition-colors hover:bg-gold-lit">
                    Request a Quote
                    <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($service['gallery'] as $img)
                    <figure data-mask-image class="relative aspect-[3/4] w-full overflow-hidden bg-navy" style="clip-path: inset(100% 0% 0% 0%)">
                        <div data-mask-inner class="h-full w-full">
                            <x-site.img :name="$img" sizes="(min-width: 1024px) 30vw, 90vw" class="h-full w-full object-cover" />
                        </div>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/40"></span>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden bg-navy py-24 sm:py-28">
        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>
        <div class="container-wide relative">
            <span class="eyebrow flex items-center gap-3.5 text-gold">
                <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Keep looking
            </span>
            <h2 data-split class="display-lg mt-6 max-w-xl text-cream" style="visibility:hidden">Other things we cater.</h2>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                @foreach ($others as $other)
                    <a href="{{ route('site.service', $other['slug']) }}" data-reveal
                       class="reveal group relative flex min-h-[20rem] items-end overflow-hidden bg-ink"
                       style="--reveal-from: translate3d(0, 2.5rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:{{ $loop->index * 0.07 }}s">
                        <x-site.img :name="$other['image']" :alt="$other['title']" sizes="(min-width: 1024px) 31vw, (min-width: 640px) 47vw, 92vw"
                             class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1300ms] group-hover:scale-[1.06]" />
                        <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink via-ink/58 to-ink/10"></span>
                        <div class="relative z-10 p-7">
                            <span class="font-display text-2xl leading-none font-light text-gold tabular-nums">{{ $other['index'] }}</span>
                            <h3 class="display-md mt-3 text-cream">{{ $other['title'] }}</h3>
                            <p class="mt-2.5 text-sm leading-relaxed text-cream/60">{{ $other['short'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @include('site.partials.cta')
@endsection
