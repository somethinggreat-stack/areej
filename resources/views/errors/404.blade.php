@extends('site.layout')

@php
    $mc = config('midland');

    $elsewhere = [
        ['label' => 'The menu', 'detail' => 'Every dish we cook', 'route' => 'site.menu'],
        ['label' => 'What we cater', 'detail' => 'Seven kinds of occasion', 'route' => 'site.services'],
        ['label' => 'Recent work', 'detail' => 'Rooms we have fed', 'route' => 'site.gallery'],
        ['label' => 'Get a quote', 'detail' => 'Four short steps', 'route' => 'site.contact'],
    ];
@endphp

@section('title', 'Page not found')
@section('description', 'That page could not be found. Browse the menu, our services or get a catering quote from Midland Catering, Birmingham.')

@section('content')
    <section class="grain relative isolate flex min-h-[100svh] items-center overflow-hidden bg-ink pt-32 pb-24">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(65%_55%_at_50%_0%,rgba(11,63,198,0.3),transparent_68%)]"></div>
        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>

        <span data-parallax="-14" aria-hidden="true"
              class="font-display pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-[clamp(12rem,40vw,32rem)] leading-none font-light whitespace-nowrap select-none text-transparent"
              style="-webkit-text-stroke: 1px color-mix(in oklab, var(--color-gold) 18%, transparent)">404</span>

        <div class="container-x relative grid gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-20">
            <div>
                <span class="eyebrow flex items-center gap-3.5 text-gold">
                    <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Nothing served here
                </span>

                <h1 data-split class="display-xl mt-7 text-cream" style="visibility:hidden">This dish came<br>off the menu.</h1>

                <p class="body-lg mt-7 max-w-lg text-cream/68">
                    The page you were after has moved or never existed. The kitchen is still open &mdash;
                    try one of these, or just call us.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('site.home') }}"
                       class="group/btn inline-flex h-14 items-center gap-3 rounded-full bg-gold px-8 text-[0.7rem] font-bold tracking-[0.2em] text-ink uppercase transition-colors hover:bg-gold-lit">
                        Back to the start
                        <svg viewBox="0 0 24 24" class="size-3.5 transition-transform duration-500 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                    <a href="{{ $mc['phone_href'] }}"
                       class="inline-flex h-14 items-center gap-3 rounded-full border border-cream/25 px-7 text-[0.7rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:text-gold">
                        {{ $mc['phone'] }}
                    </a>
                </div>
            </div>

            <ul class="border-t border-cream/12">
                @foreach ($elsewhere as $link)
                    <li class="border-b border-cream/12">
                        <a href="{{ route($link['route']) }}" data-reveal
                           class="group/l flex items-center justify-between gap-6 py-6 transition-colors">
                            <span>
                                <span class="font-display block text-2xl font-light text-cream transition-colors duration-500 group-hover/l:text-gold sm:text-3xl">{{ $link['label'] }}</span>
                                <span class="mt-1.5 block text-[0.74rem] text-cream/58">{{ $link['detail'] }}</span>
                            </span>
                            <span aria-hidden="true" class="grid size-11 shrink-0 place-items-center rounded-full border border-cream/20 text-cream/60 transition-colors duration-500 group-hover/l:border-gold group-hover/l:bg-gold group-hover/l:text-ink">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endsection
