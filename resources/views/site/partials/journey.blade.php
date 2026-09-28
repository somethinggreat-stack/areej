@php
    $mc = config('midland');
    $services = config('catering_services');

    $points = [
        'Cooked in our own kitchen — nothing is bought in',
        'Collection, delivery, or delivered and served by our staff',
        'Same-day and short-notice bookings taken',
    ];
@endphp

{{-- The intro holds its place while the services travel past it. A sticky
     column behaves the same on a phone as on a desktop, which a pinned
     horizontal track never does. --}}
<section data-journey class="relative isolate bg-ink py-24 sm:py-32" aria-label="Our services">
    <div class="container-wide grid gap-14 lg:grid-cols-[0.88fr_1.12fr] lg:gap-16">

        {{-- ------------------------------ Intro ------------------------------ --}}
        <div data-journey-intro class="lg:sticky lg:top-[16vh] lg:self-start lg:pb-24">
            <span class="eyebrow flex items-center gap-3.5 text-gold">
                <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> What we cater
            </span>

            <h2 data-split class="display-lg mt-6 text-cream" style="visibility:hidden">Seven ways we<br>feed a room.</h2>

            <p class="mt-7 max-w-md text-cream/58">
                Every service below runs from the same kitchen and the same standard &mdash; what
                changes is the scale, the timing and the way it reaches the table.
            </p>

            <ul class="mt-8 space-y-3.5">
                @foreach ($points as $point)
                    <li class="flex items-start gap-3.5 text-[0.94rem] leading-relaxed text-cream/70">
                        <span aria-hidden="true" class="mt-[0.6rem] inline-block size-1.5 shrink-0 rotate-45 bg-gold"></span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('site.contact') }}"
               class="group/btn mt-10 inline-flex h-14 items-center gap-3 rounded-full bg-gold px-8 text-[0.74rem] font-bold tracking-[0.22em] text-ink uppercase transition-colors hover:bg-gold-lit">
                Request a Quote
                <svg viewBox="0 0 24 24" class="size-3.5 transition-transform duration-500 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>
        </div>

        {{-- ------------------------------ Cards ------------------------------ --}}
        <ul class="space-y-6">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('site.service', $service['slug']) }}" data-service-card data-cursor="Open"
                       class="group relative flex min-h-[25rem] flex-col justify-between overflow-hidden p-8 sm:min-h-[29rem] sm:p-10">
                        <x-site.img :name="$service['image']" :alt="$service['title']"
                                    sizes="(min-width: 1024px) 54vw, 92vw"
                                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1300ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.05]" />
                        <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-b from-ink/90 via-ink/58 to-ink/92"></span>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/0 transition-colors duration-700 group-hover:border-gold/45"></span>

                        <div class="relative">
                            <h3 class="display-lg text-cream">
                                {{ $service['title'] }}<span class="font-sans text-[0.82rem] font-semibold text-gold/70 tabular-nums"> ({{ $service['index'] }})</span>
                            </h3>
                            <p class="mt-4 max-w-md leading-relaxed text-cream/68">{{ $service['short'] }}</p>
                        </div>

                        <div class="relative mt-12 flex items-end justify-between gap-6">
                            <div>
                                <p class="text-[0.66rem] font-semibold tracking-[0.2em] text-gold/75 uppercase">{{ $service['detail'] }}</p>
                                <span class="link-underline mt-4 inline-block text-[0.7rem] font-semibold tracking-[0.22em] text-cream uppercase">
                                    Explore service
                                </span>
                            </div>
                            <span aria-hidden="true"
                                  class="grid size-12 shrink-0 place-items-center rounded-full border border-cream/30 text-cream transition-colors duration-500 group-hover:border-gold group-hover:bg-gold group-hover:text-ink">
                                <svg viewBox="0 0 24 24" class="size-4 transition-transform duration-500 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
