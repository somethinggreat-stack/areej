@extends('site.layout')

@php
    $mc = config('midland');

    $values = [
        ['01', 'Cook it ourselves', 'Every dish leaves our own kitchen. Nothing is bought in, reheated and passed off as ours.'],
        ['02', 'Halal without exception', 'One supply chain, one standard, across every service we run. No separate lines, no compromises.'],
        ['03', 'Turn up early', 'The service line is set and holding temperature before your first guest walks in. Every time.'],
        ['04', 'Answer the phone', 'Short-notice funerals, changed guest numbers, a venue that moved the timings. We pick up.'],
    ];
@endphp

@section('title', 'Our Story')
@section('description', 'Midland Catering has cooked for Birmingham weddings, funerals, functions and corporate events from Landor Street, Saltley.')

@section('content')
    <x-site.page-hero
        eyebrow="Landor Street, Saltley"
        :lines="['A Birmingham', 'kitchen, not', 'a middleman.']"
        lede="Midland Catering has been feeding this city’s weddings, funerals and functions from a working kitchen behind a blue and gold shopfront on Landor Street."
        image="midland-premises"
        :meta="[$mc['speciality'], 'Halal', 'Est. Birmingham']" />

    <section class="relative isolate overflow-hidden bg-ink py-24 sm:py-32">
        <div class="container-x grid gap-14 lg:grid-cols-[1fr_0.9fr] lg:gap-20">
            <div>
                <span class="eyebrow flex items-center gap-3.5 text-gold">
                    <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Where we work
                </span>
                <h2 data-split class="display-lg mt-6 text-cream" style="visibility:hidden">You can come and see the kitchen.</h2>

                <div class="body-lg mt-8 space-y-6 text-cream/62">
                    <p data-reveal class="reveal" style="--reveal-from: translate3d(0, 2rem, 0); --reveal-blur: 0px; transition-duration:.9s">
                        Most catering enquiries in this city end up with someone who will subcontract the cooking.
                        We are the other end of that chain — the people with the burners, the deghs and the van.
                    </p>
                    <p data-reveal class="reveal" style="--reveal-from: translate3d(0, 2rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:.08s">
                        The unit on {{ $mc['address']['line1'] }} holds the kitchen, the cold store, the service ware
                        and the transport. When you book us, the food is prepared here on the day and driven to you hot.
                    </p>
                    <p data-reveal class="reveal" style="--reveal-from: translate3d(0, 2rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:.16s">
                        More than four decades in this trade sit behind the business, and the promise on the sign
                        outside — for events with a better taste — is the standard the kitchen is held to.
                    </p>
                </div>

                <dl class="mt-12 grid grid-cols-3 gap-6 border-t border-cream/12 pt-9">
                    @foreach ([['43+', 'Years in the trade'], ['2,000', 'Guests at full stretch'], ['100%', 'Halal']] as [$value, $label])
                        <div>
                            <dt class="sr-only">{{ $label }}</dt>
                            <dd>
                                <span class="font-display block text-[clamp(2rem,3.4vw,3rem)] leading-none font-light text-gold">{{ $value }}</span>
                                <span class="mt-3 flex items-start gap-2 text-[0.66rem] leading-snug tracking-[0.2em] text-cream/58 uppercase">
                                    <span aria-hidden="true" class="mt-1.5 inline-block size-1.5 shrink-0 rotate-45 bg-gold"></span>{{ $label }}
                                </span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['midland-premises-portrait', 'commercial-kitchen', 'client-chafing-curry', 'spices-flatlay'] as $img)
                    <figure data-mask-image class="relative aspect-[4/5] w-full overflow-hidden bg-navy" style="clip-path: inset(100% 0% 0% 0%)">
                        <div data-mask-inner class="h-full w-full">
                            <x-site.img :name="$img" sizes="(min-width: 1024px) 24vw, 45vw" class="h-full w-full object-cover" />
                        </div>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/40"></span>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden bg-cream py-24 text-ink sm:py-32">
        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>
        <div class="container-x relative">
            <span class="eyebrow flex items-center gap-3.5 text-gold-ink">
                <span aria-hidden="true" class="h-px w-10 bg-gold-ink/70"></span> How we work
            </span>
            <h2 data-split class="display-lg mt-6 text-ink" style="visibility:hidden">Four rules we do not bend.</h2>

            <div class="mt-14 grid gap-px overflow-hidden border border-ink/12 bg-ink/12 sm:grid-cols-2">
                @foreach ($values as [$n, $title, $body])
                    <article data-reveal class="reveal group relative h-full bg-cream p-8 transition-colors duration-700 hover:bg-paper sm:p-10"
                             style="--reveal-from: translate3d(0, 2rem, 0); --reveal-blur: 0px; transition-duration:.9s; transition-delay:{{ $loop->index * 0.07 }}s">
                        <span class="font-display text-3xl leading-none font-light text-gold-ink tabular-nums">{{ $n }}</span>
                        <h3 class="display-md mt-5 text-ink">{{ $title }}</h3>
                        <p class="mt-3.5 leading-relaxed text-ink/60">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @include('site.partials.reviews')
    @include('site.partials.map')
    @include('site.partials.cta')
@endsection
