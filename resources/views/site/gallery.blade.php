@extends('site.layout')

@php
    $mc = config('midland');
    $items = config('story.gallery');
    $categories = config('story.gallery_categories');

    $span = fn (?string $s) => match ($s) {
        'wide' => 'row-span-2 sm:col-span-2 sm:row-span-3',
        'tall' => 'row-span-3 sm:row-span-4',
        default => 'row-span-2 sm:row-span-3',
    };
@endphp

@section('title', 'Gallery')
@section('description', 'Weddings, corporate events, private parties and community occasions catered by Midland Catering across Birmingham and the West Midlands.')

@section('content')
    <x-site.page-hero
        eyebrow="Recent work"
        :lines="['Rooms we', 'have fed.']"
        lede="Four hundred covers in a banqueting hall, thirty in a front room, two hundred delegates through lunch in twenty-five minutes. The job changes shape every week."
        image="banquet-hall"
        :meta="['Weddings', 'Corporate', 'Private', 'Food']" />

    <section data-gallery class="relative isolate overflow-hidden bg-ink py-24 sm:py-32">
        <div class="container-wide">
            <div class="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Portfolio
                    </span>
                    <h2 data-split class="display-lg mt-6 text-cream" style="visibility:hidden">Every room is different.</h2>
                </div>

                <div role="tablist" aria-label="Filter gallery by event type" class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                    @foreach ($categories as $category)
                        <button type="button" role="tab" data-filter="{{ $category }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                class="shrink-0 rounded-full border border-cream/18 px-5 py-2.5 text-[0.66rem] font-semibold tracking-[0.2em] text-cream/58 uppercase transition-colors duration-500 hover:border-cream/45 hover:text-cream aria-selected:border-gold aria-selected:bg-gold aria-selected:text-ink">
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-14 grid auto-rows-[8.5rem] grid-flow-row-dense grid-cols-1 gap-4 sm:auto-rows-[9.5rem] sm:grid-cols-2 lg:auto-rows-[10.5rem] lg:grid-cols-3 lg:gap-5">
                @foreach ($items as $item)
                    @php($tilt = [-0.8, 0.5, -0.35, 0.75, -0.6, 0.3][$loop->index % 6])
                    <button type="button" data-tile
                            data-category="{{ $item['category'] }}"
                            data-caption="{{ $item['caption'] }}"
                            data-full="{{ asset('img/'.$item['file'].'.jpg') }}"
                            data-thumb="{{ asset('img/r/'.$item['file'].'-480.jpg') }}"
                            aria-label="Open {{ $item['caption'] }}"
                            class="group relative overflow-hidden bg-navy text-left {{ $span($item['span']) }}"
                            style="rotate: {{ $tilt }}deg">
                        <x-site.img :name="$item['file']" :alt="$item['caption']" sizes="(min-width: 1024px) 31vw, (min-width: 640px) 47vw, 92vw"
                             class="h-full w-full scale-[1.04] object-cover transition-transform duration-[1300ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.12]" />
                        <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink/88 via-ink/25 to-transparent opacity-70 transition-opacity duration-700 group-hover:opacity-95"></span>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/0 transition-colors duration-[900ms] group-hover:border-gold/45"></span>
                        <span class="absolute inset-x-0 bottom-0 translate-y-2 p-5 opacity-0 transition-all duration-[700ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:translate-y-0 group-hover:opacity-100">
                            <span class="block text-[0.58rem] font-bold tracking-[0.2em] text-gold uppercase">{{ $item['category'] }}</span>
                            <span class="mt-1.5 block text-sm text-cream">{{ $item['caption'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>

            <p class="mt-10 text-[0.78rem] text-cream/58">
                Showing <span data-gallery-count>{{ count($items) }}</span> of {{ count($items) }} images.
                Photography will be replaced with Midland Catering&rsquo;s own event shoot once available.
            </p>
        </div>
    </section>

    @include('site.partials.lightbox')
    @include('site.partials.cta')
@endsection
