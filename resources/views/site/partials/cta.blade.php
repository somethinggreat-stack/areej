@php
    $mc = config('midland');
@endphp

<section class="grain relative isolate flex min-h-[80svh] items-center overflow-hidden bg-navy py-24 sm:py-32" aria-label="Request a quote">
    <div class="absolute inset-0 -z-20">
        <x-site.img name="buffet-chafing" sizes="100vw" class="h-full w-full object-cover" />
        <span aria-hidden="true" class="absolute inset-0 bg-navy/86"></span>
        <span aria-hidden="true" class="absolute inset-0 bg-[radial-gradient(65%_60%_at_50%_45%,rgba(11,63,198,0.32),transparent_72%)]"></span>
        <span aria-hidden="true" class="brand-pattern absolute inset-0 opacity-[0.05]"></span>
    </div>

    <div class="container-x relative">
        <div class="relative mx-auto max-w-4xl border border-gold/40 px-6 py-14 text-center sm:px-12 sm:py-20">
            <div class="flex justify-center">
                <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="72" height="72" class="h-[4.5rem] w-[4.5rem] object-contain">
            </div>

            <h2 data-split class="display-xl mt-8 text-cream" style="visibility:hidden">Let's plan your event.</h2>

            <p class="body-lg mx-auto mt-7 max-w-xl text-cream/68">
                Tell us the date, the venue and roughly how many people you are feeding.
                We will come back with a menu and a price — usually within a working day.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('site.contact') }}"
                   class="inline-flex h-14 items-center gap-3 rounded-full bg-gold px-9 text-[0.74rem] font-bold tracking-[0.2em] text-ink uppercase transition-colors hover:bg-gold-lit">
                    Request a Quote
                </a>
                <a href="{{ $mc['phone_href'] }}"
                   class="inline-flex h-14 items-center rounded-full border border-cream/25 px-9 text-[0.74rem] font-bold tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:text-gold">
                    {{ $mc['phone'] }}
                </a>
            </div>

            <ul class="mt-11 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-[0.66rem] font-semibold tracking-[0.2em] text-cream/58 uppercase">
                @foreach ($mc['credentials'] as $c)
                    <li class="flex items-center gap-2.5"><span aria-hidden="true" class="size-1 rotate-45 bg-gold"></span>{{ $c }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
