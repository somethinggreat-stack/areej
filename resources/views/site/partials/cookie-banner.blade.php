{{--
    Asks once about optional cookies (only Google Maps today). Hidden until the
    script finds no saved choice, so a visitor who already chose never sees it,
    and the page works the same without JavaScript: nothing optional loads.
--}}
<div data-cookie-banner role="dialog" aria-modal="false" aria-labelledby="cookie-banner-title" hidden
     class="fixed inset-x-3 bottom-3 z-[95] mx-auto max-w-2xl border border-cream/15 bg-navy/97 p-5 text-cream shadow-2xl backdrop-blur sm:inset-x-6 sm:bottom-6 sm:p-6">
    <h2 id="cookie-banner-title" class="text-sm font-semibold">Cookies on this website</h2>
    <p class="mt-2 text-sm leading-relaxed text-cream/75">
        We only use the cookies the website needs to work. With your permission we will also show the Google map of our kitchen,
        which lets Google set its own cookies. No analytics or advertising.
        <a href="{{ route('site.cookies') }}" class="link-underline text-cream">Cookie policy</a>
    </p>
    <div class="mt-4 flex flex-wrap gap-3">
        <button type="button" data-cookie-choice="accepted"
                class="inline-flex h-11 items-center rounded-full bg-gold px-6 text-[0.7rem] font-bold tracking-[0.18em] text-ink uppercase transition-colors hover:bg-gold-lit">
            Accept
        </button>
        <button type="button" data-cookie-choice="rejected"
                class="inline-flex h-11 items-center rounded-full border border-cream/30 px-6 text-[0.7rem] font-bold tracking-[0.18em] text-cream uppercase transition-colors hover:bg-white/10">
            Reject
        </button>
    </div>
</div>
