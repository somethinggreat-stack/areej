@extends('site.layout')

@php
    $mc = config('midland');
    $menu = config('menu');
@endphp

@section('title', 'Our Menu')
@section('description', 'The full Midland Catering menu — appetisers, starters, mains, rice and breads, desserts and hot drinks. Halal throughout, from 20 to 2,000 guests.')

@section('content')
    <x-site.page-hero
        eyebrow="Build your own menu"
        :lines="['Food that', 'tastes like', 'someone’s home.']"
        lede="Traditional Pakistani and North Indian cooking, scaled for events. Whole spices ground in-house, meat marinated overnight, breads cooked to order."
        image="curry-trio"
        :meta="['Halal', 'Vegetarian options', 'Allergen labelling']" />

    {{-- Every course reads straight down the page. Nothing is hidden behind a
         control the visitor has to find first. --}}
    <div data-menu class="relative">
        @foreach ($menu as $i => $cat)
            @php($dark = $i % 2 === 0)
            <section id="cat-{{ $cat['id'] }}"
                     aria-labelledby="cat-{{ $cat['id'] }}-title"
                     class="relative isolate overflow-hidden py-20 scroll-mt-24 sm:py-28 {{ $dark ? 'grain bg-ink text-cream' : 'bg-cream text-ink' }}">
                @unless ($dark)
                    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>
                @endunless

                <div class="container-wide relative grid items-start gap-12 lg:grid-cols-[0.82fr_1.18fr] lg:gap-16">
                    <div>
                        <div class="flex items-baseline gap-4">
                            <span class="font-display text-4xl leading-none font-light tabular-nums {{ $dark ? 'text-gold' : 'text-gold-ink' }}">{{ $cat['n'] }}</span>
                            <span class="h-px flex-1 {{ $dark ? 'bg-cream/20' : 'bg-ink/15' }}"></span>
                        </div>

                        <h2 id="cat-{{ $cat['id'] }}-title" data-split
                            class="display-lg mt-5 {{ $dark ? 'text-cream' : 'text-ink' }}" style="visibility:hidden">{{ $cat['title'] }}</h2>
                        <p class="eyebrow mt-3 {{ $dark ? 'text-gold/80' : 'text-gold-ink' }}">{{ $cat['sub'] }}</p>
                        <p class="mt-5 max-w-sm leading-relaxed {{ $dark ? 'text-cream/60' : 'text-ink/62' }}">{{ $cat['blurb'] }}</p>

                        <figure class="relative mt-9 aspect-[4/5] w-full max-w-md overflow-hidden">
                            <x-site.img :name="$cat['image']" :alt="$cat['title']" sizes="(min-width: 1024px) 40vw, 90vw" class="h-full w-full object-cover" />
                            <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/45"></span>
                        </figure>
                    </div>

                    <div>
                        <div data-dish-grid class="grid gap-x-12 sm:grid-cols-2">
                            @foreach (array_chunk($cat['dishes'], (int) ceil(count($cat['dishes']) / 2)) as $column)
                                <ul>
                                    @foreach ($column as $dish)
                                        <li data-dish class="border-b py-4 {{ $dark ? 'border-cream/10' : 'border-ink/10' }}">
                                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1.5">
                                                <h3 class="text-[1.02rem] font-medium {{ $dark ? 'text-cream' : 'text-ink' }}">{{ $dish['name'] }}</h3>
                                                @if ($dish['signature'])
                                                    <span class="rounded-full border px-2 py-0.5 text-[0.53rem] font-bold tracking-[0.2em] uppercase {{ $dark ? 'border-gold/45 text-gold' : 'border-gold-ink/45 text-gold-ink' }}">Signature</span>
                                                @endif
                                                @foreach ($dish['tags'] as $tag)
                                                    <span class="text-[0.6rem] font-bold tracking-[0.12em] {{ $dark ? 'text-cream/58' : 'text-ink/60' }}">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                            <p class="mt-1.5 text-[0.86rem] leading-relaxed {{ $dark ? 'text-cream/58' : 'text-ink/60' }}">{{ $dish['desc'] }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>

                        <figure class="relative mt-10 aspect-[16/9] w-full overflow-hidden">
                            <x-site.img :name="$cat['accent']" sizes="(min-width: 1024px) 30vw, 90vw" class="h-full w-full object-cover" />
                            <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/40"></span>
                        </figure>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <section class="bg-navy py-14" aria-label="Dietary key">
        <div class="container-x">
            <span class="eyebrow flex items-center gap-3.5 text-gold">
                <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Dietary key
            </span>
            <ul class="mt-7 flex flex-wrap gap-x-10 gap-y-4">
                @foreach ($mc['dietary_key'] as $k)
                    <li class="flex items-center gap-3">
                        <span class="grid h-8 place-items-center rounded-full border border-gold/40 px-3 text-[0.62rem] font-bold text-gold">{{ $k['code'] }}</span>
                        <span class="text-sm text-cream/70">{{ $k['label'] }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-8 max-w-3xl text-[0.78rem] leading-relaxed text-cream/58">
                Build your own menu from any of the courses above. Quantities and spice levels are agreed for each
                booking, and pricing is quoted against guest numbers and service style — minimum {{ $mc['guests']['min'] }}
                guests for full catering, or fewer if you are ordering starters only. Please tell us about allergies
                when you enquire and we will confirm exactly what is in each dish before your event.
            </p>
        </div>
    </section>

    @include('site.partials.cta')
@endsection
