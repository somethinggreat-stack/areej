@php
    $mc = config('midland');
    $reviews = app(\App\Services\GoogleReviews::class)->get();
    $backdrops = ['banquet-hall', 'biryani-platter-red', 'conference-hall', 'wedding-long-table', 'buffet-chafing'];
@endphp

<section data-testimonials tabindex="0" role="group" aria-roledescription="carousel" aria-label="Client testimonials"
         class="grain relative isolate overflow-hidden bg-navy py-24 outline-offset-8 sm:py-32">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_60%_at_20%_10%,rgba(11,63,198,0.28),transparent_65%)]"></div>
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.04]"></div>

    <div class="container-x relative">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <span class="eyebrow flex items-center gap-3.5 text-gold">
                    <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> In their words
                </span>
                <h2 data-split class="display-lg mt-6 max-w-lg text-cream" style="visibility:hidden">
                    The compliment we want is silence about the food.
                </h2>
            </div>

            {{-- Live rating from their own Google Business Profile --}}
            <a href="{{ $mc['google']['reviews_url'] }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-3.5 rounded-full border border-cream/20 py-2.5 pr-5 pl-3 transition-colors hover:border-gold/60">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-cream">
                    <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true">
                        <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5a5.6 5.6 0 0 1-2.4 3.7v3h3.9c2.3-2.1 3.5-5.2 3.5-8.9z"/>
                        <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1 .7-2.3 1.1-4 1.1-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z"/>
                        <path fill="#FBBC05" d="M5.4 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.4a12 12 0 0 0 0 10.8z"/>
                        <path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.4 6.6l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/>
                    </svg>
                </span>
                <span class="leading-tight">
                    <span class="flex items-center gap-1.5">
                        <span class="font-display text-lg text-cream">{{ number_format($reviews['rating'], 1) }}</span>
                        <span class="flex" aria-hidden="true">
                            @for ($i = 0; $i < 5; $i++)
                                <svg viewBox="0 0 24 24" class="size-3.5 {{ $i < round($reviews['rating']) ? 'text-gold' : 'text-cream/25' }}" fill="currentColor">
                                    <path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8 5.8 21l1.2-6.9-5-4.9 6.9-1z"/>
                                </svg>
                            @endfor
                        </span>
                    </span>
                    <span class="mt-0.5 block text-[0.66rem] tracking-[0.12em] text-cream/58 uppercase">{{ $reviews['total'] }} Google reviews</span>
                </span>
            </a>
        </div>

        {{-- Carousel: quote on one side, event image on the other --}}
        <div class="relative mt-14 grid gap-10 lg:mt-16 lg:grid-cols-[1.25fr_0.75fr] lg:items-stretch lg:gap-16">
            <div class="relative flex flex-col">
                <svg viewBox="0 0 48 36" class="h-9 w-12 shrink-0 text-gold/45" fill="currentColor" aria-hidden="true">
                    <path d="M20 0v14a22 22 0 0 1-14 22v-7a15 15 0 0 0 8-11H6V0zm28 0v14a22 22 0 0 1-14 22v-7a15 15 0 0 0 8-11h-8V0z" />
                </svg>

                <div class="mt-7 grid flex-1">
                    @foreach ($reviews['reviews'] as $review)
                        <figure data-testimonial class="col-start-1 row-start-1" style="opacity: {{ $loop->first ? 1 : 0 }}; pointer-events: {{ $loop->first ? 'auto' : 'none' }}">
                            <blockquote class="font-display text-[clamp(1.3rem,2.6vw,2.2rem)] leading-[1.28] font-light text-cream">
                                “{{ $review['text'] }}”
                            </blockquote>
                            <figcaption class="mt-7">
                                <span role="img" class="flex gap-0.5" aria-label="{{ $review['rating'] }} out of 5 stars">
                                    @for ($i = 0; $i < 5; $i++)
                                        <svg viewBox="0 0 24 24" class="size-3.5 {{ $i < $review['rating'] ? 'text-gold' : 'text-cream/20' }}" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8 5.8 21l1.2-6.9-5-4.9 6.9-1z"/>
                                        </svg>
                                    @endfor
                                </span>
                                <p class="mt-3 text-[0.72rem] font-semibold tracking-[0.2em] text-gold uppercase">{{ $review['author'] }}</p>
                                <p class="mt-1.5 text-sm text-cream/58">{{ $review['when'] }}</p>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                {{-- Controls sit under the tallest quote --}}
                <div class="mt-10">
                    <div class="h-px w-full bg-cream/15"></div>
                    <div class="mt-5 flex items-center justify-end gap-3">
                        <button type="button" data-testimonial-prev aria-label="Previous testimonial"
                                class="group/c grid size-11 place-items-center rounded-full border border-cream/22 text-cream/70 transition-colors duration-500 hover:border-gold hover:bg-gold hover:text-ink">
                            <svg viewBox="0 0 24 24" class="size-4 rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </button>

                        <div class="flex items-center gap-1.5 px-1">
                            @foreach ($reviews['reviews'] as $review)
                                <button type="button" data-testimonial-dot aria-label="Go to testimonial {{ $loop->iteration }}"
                                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                        class="grid h-11 place-items-center px-1">
                                    <span class="block h-[3px] rounded-full transition-all duration-600"
                                          style="width: {{ $loop->first ? '2rem' : '0.75rem' }}; background-color: {{ $loop->first ? 'var(--color-gold)' : 'rgba(244,238,226,0.35)' }}"></span>
                                </button>
                            @endforeach
                        </div>

                        <button type="button" data-testimonial-next aria-label="Next testimonial"
                                class="group/c grid size-11 place-items-center rounded-full border border-cream/22 text-cream/70 transition-colors duration-500 hover:border-gold hover:bg-gold hover:text-ink">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="relative aspect-3/2 w-full overflow-hidden bg-ink max-lg:order-first lg:aspect-auto lg:h-full lg:max-h-[28rem] lg:self-center">
                @foreach ($reviews['reviews'] as $review)
                    <x-site.img :name="$backdrops[$loop->index % count($backdrops)]" data-testimonial-image
                                sizes="(min-width: 1024px) 30vw, 90vw"
                                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-[1000ms]"
                                style="opacity: {{ $loop->first ? 1 : 0 }}" />
                @endforeach
                <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-navy/70 via-transparent to-transparent"></span>
                <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/40"></span>
            </div>
        </div>

        <p class="mt-10 max-w-2xl text-[0.78rem] leading-relaxed text-cream/58">
            Verified reviews from our Google Business Profile.
            <a href="{{ $mc['google']['reviews_url'] }}" target="_blank" rel="noopener noreferrer" class="link-underline text-gold">Read all {{ $reviews['total'] }} reviews</a>
            ·
            <a href="{{ $mc['google']['write_review_url'] }}" target="_blank" rel="noopener noreferrer" class="link-underline text-cream/70">Leave a review</a>
        </p>
    </div>
</section>
