@php
    $mc = config('midland');
    $services = config('catering_services');
    $links = [
        ['route' => 'site.home', 'label' => 'Home'],
        ['route' => 'site.services', 'label' => 'Services'],
        ['route' => 'site.menu', 'label' => 'Our Menu'],
        ['route' => 'site.gallery', 'label' => 'Gallery'],
        ['route' => 'site.about', 'label' => 'Our Story'],
        ['route' => 'site.contact', 'label' => 'Contact'],
    ];
@endphp

<footer class="grain relative isolate overflow-hidden bg-ink text-cream">
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.04]"></div>

    <div class="relative border-y border-cream/10 py-6 sm:py-8">
        <x-site.marquee :items="[
            'Let\'s plan your event',
            $mc['phone'],
            'Birmingham',
            $mc['tagline'],
        ]" :speed="46" item-class="font-display text-[clamp(1.9rem,5.4vw,4.25rem)] font-light tracking-[-0.03em] text-cream/80" />
    </div>

    <div class="container-wide relative z-10 py-16 sm:py-20">
        <div class="grid gap-14 lg:grid-cols-[1.15fr_2fr]">
            <div>
                <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="80" height="80" class="h-20 w-20 object-contain">
                <p class="font-display mt-6 max-w-sm text-[clamp(1.4rem,2.1vw,1.85rem)] leading-[1.16] font-light text-cream">
                    Traditional Asian catering for Birmingham's biggest days.
                </p>
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-cream/58">
                    {{ $mc['speciality'] }}. {{ $mc['halal'] }} — {{ strtolower($mc['supervision']) }}.
                    Catering for {{ $mc['guests']['min'] }} to {{ number_format($mc['guests']['max']) }} guests
                    across Birmingham and the West Midlands.
                </p>
            </div>

            <div class="grid gap-10 sm:grid-cols-3">
                <nav aria-label="Footer navigation">
                    <h2 class="eyebrow mb-6 text-gold/70">Explore</h2>
                    <ul class="space-y-3.5">
                        @foreach ($links as $l)
                            <li><a href="{{ route($l['route']) }}" class="link-underline text-sm text-cream/70 transition-colors hover:text-cream">{{ $l['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <nav aria-label="Services">
                    <h2 class="eyebrow mb-6 text-gold/70">Services</h2>
                    <ul class="space-y-3.5">
                        @foreach ($services as $s)
                            <li><a href="{{ route('site.service', $s['slug']) }}" class="link-underline text-sm text-cream/70 transition-colors hover:text-cream">{{ $s['title'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <div>
                    <h2 class="eyebrow mb-6 text-gold/70">Visit &amp; Contact</h2>
                    <address class="space-y-4 text-sm leading-relaxed text-cream/70 not-italic">
                        <p>
                            {{ $mc['address']['line1'] }}<br>
                            {{ $mc['address']['line2'] }}<br>
                            {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}
                        </p>
                        <p>
                            <a href="{{ $mc['phone_href'] }}" class="link-underline block text-cream">{{ $mc['phone'] }}</a>
                            <a href="{{ $mc['email_href'] }}" class="link-underline mt-1.5 block break-all">{{ $mc['email'] }}</a>
                        </p>
                    </address>

                    <div class="mt-6">
                        <h3 class="eyebrow mb-3 text-gold/70">Contact hours</h3>
                        <p class="text-[0.8rem] text-cream/58">{{ $mc['contact_hours'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 flex flex-col gap-6 border-t border-cream/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[0.72rem] tracking-[0.2em] text-cream/58">
                © {{ date('Y') }} {{ $mc['legal_name'] }}. All rights reserved.
            </p>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <a href="{{ $mc['maps_url'] }}" target="_blank" rel="noopener noreferrer"
                   class="link-underline text-[0.7rem] font-semibold tracking-[0.2em] text-cream/58 uppercase transition-colors hover:text-gold">Directions</a>
                <a href="{{ $mc['google']['reviews_url'] }}" target="_blank" rel="noopener noreferrer"
                   class="link-underline text-[0.7rem] font-semibold tracking-[0.2em] text-cream/58 uppercase transition-colors hover:text-gold">
                    {{ $mc['google']['rating'] }}★ Google Reviews
                </a>
            </div>
        </div>
    </div>
</footer>
