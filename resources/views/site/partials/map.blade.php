@php
    $mc = config('midland');
@endphp

<section class="relative isolate overflow-hidden bg-cream py-20 text-ink sm:py-24" aria-label="Find us">
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>

    <div class="container-wide relative grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center lg:gap-16">
        <div>
            <span class="eyebrow flex items-center gap-3.5 text-gold-ink">
                <span aria-hidden="true" class="h-px w-10 bg-gold-ink/70"></span> Find the kitchen
            </span>
            <h2 data-split class="display-lg mt-6 text-ink" style="visibility:hidden">Landor Street, Saltley.</h2>

            <address class="mt-6 text-lg leading-relaxed text-ink/70 not-italic">
                {{ $mc['address']['line1'] }}<br>
                {{ $mc['address']['line2'] }}<br>
                {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}
            </address>

            <dl class="mt-7 space-y-2 text-sm">
                <div class="flex gap-3">
                    <dt class="w-24 shrink-0 text-ink/60">Phone</dt>
                    <dd><a href="{{ $mc['phone_href'] }}" class="link-underline font-medium text-ink">{{ $mc['phone'] }}</a></dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-24 shrink-0 text-ink/60">Hours</dt>
                    <dd class="text-ink/80">{{ $mc['contact_hours'] }}</dd>
                </div>
            </dl>

            <a href="{{ $mc['maps_url'] }}" target="_blank" rel="noopener noreferrer"
               class="mt-8 inline-flex h-12 items-center gap-3 rounded-full bg-navy px-7 text-[0.7rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors hover:bg-royal-deep">
                Get directions
                <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
            </a>
        </div>

        <div class="relative overflow-hidden border border-ink/10 bg-sand">
            {{-- Pinned on the exact coordinates from their Google Business Profile,
                 not a geocoded street name, which lands mid-road. --}}
            <iframe
                src="https://www.google.com/maps?q={{ $mc['geo']['lat'] }},{{ $mc['geo']['lng'] }}&z=17&hl=en&output=embed"
                title="Map showing Midland Catering, Unit 3 Landor Street, Birmingham B8 1AG"
                class="h-[22rem] w-full lg:h-[26rem]"
                loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="border:0"></iframe>
        </div>
    </div>
</section>
