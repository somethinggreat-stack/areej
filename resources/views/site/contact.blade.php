@extends('site.layout')

@php
    $mc = config('midland');
    $services = config('catering_services');

    $styles = ['Delivery', 'Collection', 'Delivered and served', 'Full buffet setup', 'Not sure yet'];
    $extras = ['Kitchen serving staff', 'Waiter service', 'Cutlery & crockery hire', 'Serving dishes', 'Venue hire', 'Hot drinks station'];

    $fieldClass = 'mt-2 w-full border border-cream/20 bg-ink/40 px-4 py-3.5 text-base text-cream outline-none transition-colors placeholder:text-cream/30 focus:border-gold';
    $labelClass = 'block text-sm font-medium text-cream';
    $choiceClass = 'flex min-h-12 cursor-pointer items-center gap-3 border border-cream/18 px-4 py-3 text-sm text-cream/80 transition-colors duration-400 hover:border-cream/45 hover:text-cream has-checked:border-gold has-checked:bg-gold/10 has-checked:text-gold has-focus-visible:border-gold';
@endphp

@section('title', 'Contact & Quotes')
@section('description', 'Request a catering quote from Midland Catering, Birmingham. Call 0121 773 4778 or send your event details.')

@section('content')
    <x-site.page-hero
        eyebrow="Let’s plan your event"
        :lines="['Tell us about', 'the occasion.']"
        lede="One short form. Give us the date, the numbers and the kind of event, and we will come back with a menu and a quote — usually within a working day."
        image="event-glassware"
        :meta="['Free quotes', 'Short notice welcome', 'Halal']" />

    <section class="relative isolate overflow-hidden bg-ink py-20 sm:py-28">
        <div class="container-wide grid gap-12 lg:grid-cols-[1.35fr_0.65fr] lg:gap-16">
            <div>
                @if (session('enquiry_reference'))
                    <div role="status" class="relative overflow-hidden border border-gold/40 bg-navy p-9 text-center sm:p-14">
                        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.06]"></div>
                        <div class="relative">
                            <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="64" height="64" class="mx-auto h-16 w-16 object-contain">
                            <h2 class="display-lg mt-7 text-cream">Thank you.</h2>
                            <p class="body-lg mx-auto mt-5 max-w-lg text-cream/68">
                                Your enquiry has reached us. We will be in touch, usually within a working day.
                            </p>
                            <p class="mt-6 text-[0.72rem] tracking-[0.2em] text-cream/58 uppercase">
                                Your reference — <span class="text-gold">{{ session('enquiry_reference') }}</span>
                            </p>
                            <p class="mt-6 text-sm text-cream/58">
                                In a hurry? Call <a href="{{ $mc['phone_href'] }}" class="link-underline text-cream">{{ $mc['phone'] }}</a>
                            </p>
                        </div>
                    </div>
                @else
                    {{-- One page, one form. Only a name and a phone or email are required. --}}
                    <form data-enquiry-form method="POST" action="{{ route('site.enquiry.store') }}"
                          class="border border-cream/12 bg-navy">
                        @csrf

                        <div class="border-b border-cream/10 px-5 pt-8 pb-6 sm:px-10">
                            <span class="eyebrow text-gold">Free quote</span>
                            <h2 class="display-md mt-2 text-cream">Send us an enquiry</h2>
                            <p class="mt-3 text-base text-cream/70">
                                Fill in as much as you know. We only need your name and a phone number or email.
                            </p>
                        </div>

                        <div class="space-y-12 px-5 py-9 sm:px-10">
                            @if ($errors->any())
                                <div role="alert" class="border border-gold-lit/40 bg-gold-lit/8 px-4 py-3 text-base text-gold-lit">
                                    <p class="font-semibold">Please check the following:</p>
                                    <ul class="mt-2 list-inside list-disc space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <fieldset>
                                <legend class="display-md text-cream">How can we reach you?</legend>
                                <div class="mt-6 grid gap-6">
                                    <div>
                                        <label for="name" class="{{ $labelClass }}">Your name</label>
                                        <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}"
                                               class="{{ $fieldClass }}">
                                    </div>

                                    <div class="grid gap-6 sm:grid-cols-2">
                                        <div>
                                            <label for="phone" class="{{ $labelClass }}">Phone</label>
                                            <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone') }}"
                                                   class="{{ $fieldClass }}">
                                        </div>
                                        <div>
                                            <label for="email" class="{{ $labelClass }}">Email</label>
                                            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" value="{{ old('email') }}"
                                                   class="{{ $fieldClass }}">
                                        </div>
                                    </div>
                                    <p class="text-sm text-cream/60">A phone number or an email — either is enough for us to reply.</p>
                                </div>
                            </fieldset>

                            <fieldset class="border-t border-cream/10 pt-10">
                                <legend class="display-md float-left w-full text-cream">About your event</legend>

                                <div class="clear-both pt-6">
                                    <p class="{{ $labelClass }}" id="event_type_label">What is the occasion?</p>
                                    <div role="radiogroup" aria-labelledby="event_type_label" class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                        @foreach ($services as $s)
                                            <label class="{{ $choiceClass }}">
                                                <input type="radio" name="event_type" value="{{ $s['title'] }}" class="size-5 shrink-0 accent-gold" @checked(old('event_type') === $s['title'])>
                                                <span>{{ $s['title'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                                    <div>
                                        <label for="event_date" class="{{ $labelClass }}">Event date</label>
                                        <input id="event_date" name="event_date" type="date" value="{{ old('event_date') }}" min="{{ now()->toDateString() }}"
                                               class="{{ $fieldClass }} scheme-dark">
                                    </div>
                                    <div>
                                        <label for="guests" class="{{ $labelClass }}">Number of guests</label>
                                        <input id="guests" name="guests" type="number" inputmode="numeric" min="1" max="2000" value="{{ old('guests') }}" placeholder="e.g. 100"
                                               class="{{ $fieldClass }} tabular-nums">
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2" aria-label="Quick guest numbers">
                                    @foreach ([50, 100, 250, 500, 1000] as $n)
                                        <button type="button" data-guest-preset="{{ $n }}"
                                                class="min-h-11 rounded-full border border-cream/18 px-4 text-sm text-cream/60 tabular-nums transition-colors duration-400 hover:border-gold hover:text-gold">
                                            {{ $n }}
                                        </button>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-sm text-cream/60">
                                    Minimum {{ $mc['guests']['min'] }} for full catering, or fewer for starters only. Up to {{ number_format($mc['guests']['max']) }}.
                                </p>

                                <div class="mt-8">
                                    <label for="venue" class="{{ $labelClass }}">Venue or area <span class="font-normal text-cream/50">(optional)</span></label>
                                    <input id="venue" name="venue" type="text" value="{{ old('venue') }}" placeholder="e.g. community hall, Sparkbrook"
                                           class="{{ $fieldClass }}">
                                </div>
                            </fieldset>

                            <fieldset class="border-t border-cream/10 pt-10">
                                <legend class="display-md float-left w-full text-cream">Food and service</legend>

                                <div class="clear-both pt-6">
                                    <p class="{{ $labelClass }}" id="service_style_label">How should the food reach you?</p>
                                    <div role="radiogroup" aria-labelledby="service_style_label" class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                        @foreach ($styles as $style)
                                            <label class="{{ $choiceClass }}">
                                                <input type="radio" name="service_style" value="{{ $style }}" class="size-5 shrink-0 accent-gold" @checked(old('service_style') === $style)>
                                                <span>{{ $style }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <p class="{{ $labelClass }}" id="extras_label">Anything else you need? <span class="font-normal text-cream/50">(tick all that apply)</span></p>
                                    <div role="group" aria-labelledby="extras_label" class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                        @foreach ($extras as $extra)
                                            <label class="{{ $choiceClass }}">
                                                <input type="checkbox" name="extras[]" value="{{ $extra }}" class="size-5 shrink-0 accent-gold" @checked(in_array($extra, old('extras', []), true))>
                                                <span>{{ $extra }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <label for="dietary" class="{{ $labelClass }}">Dietary requirements <span class="font-normal text-cream/50">(optional)</span></label>
                                    <input id="dietary" name="dietary" type="text" value="{{ old('dietary') }}" placeholder="e.g. 20 vegetarian, 4 nut allergies"
                                           class="{{ $fieldClass }}">
                                </div>

                                <div class="mt-8">
                                    <label for="message" class="{{ $labelClass }}">Anything else we should know? <span class="font-normal text-cream/50">(optional)</span></label>
                                    <textarea id="message" name="message" rows="4"
                                              class="{{ $fieldClass }} resize-y">{{ old('message') }}</textarea>
                                </div>
                            </fieldset>

                            {{-- Honeypot: hidden from people, tempting to bots. --}}
                            <div class="sr-only" aria-hidden="true">
                                <label for="company_website">Leave this field empty</label>
                                <input id="company_website" name="company_website" type="text" tabindex="-1" autocomplete="off">
                            </div>
                        </div>

                        <div class="border-t border-cream/10 px-5 py-7 sm:px-10">
                            <label for="privacy_consent" class="mb-6 flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-cream/75">
                                <input id="privacy_consent" name="privacy_consent" type="checkbox" value="1" required @checked(old('privacy_consent'))
                                       @error('privacy_consent') aria-invalid="true" @enderror
                                       class="mt-0.5 size-5 shrink-0 rounded border-cream/30 bg-transparent text-gold focus:ring-2 focus:ring-gold/40">
                                <span>I agree to {{ $mc['name'] }} using these details to reply to my enquiry and quote for my event, as explained in the
                                    <a href="{{ route('site.privacy') }}" target="_blank" class="link-underline text-cream">privacy notice</a>.</span>
                            </label>
                            <button type="submit"
                                    class="group/btn inline-flex min-h-14 w-full items-center justify-center gap-3 rounded-full bg-gold px-8 py-4 text-sm font-bold tracking-[0.2em] text-ink uppercase transition-colors hover:bg-gold-lit sm:w-auto">
                                Send enquiry
                                <svg viewBox="0 0 24 24" class="size-4 transition-transform duration-500 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                            </button>
                            <p class="mt-4 text-sm text-cream/60">We usually reply within a working day. Prefer to talk? Call <a href="{{ $mc['phone_href'] }}" class="link-underline text-cream">{{ $mc['phone'] }}</a>.</p>
                        </div>
                    </form>
                @endif
            </div>

            <aside class="space-y-10">
                <div>
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Rather just call?
                    </span>
                    <a href="{{ $mc['phone_href'] }}" class="group/ph mt-6 flex items-center gap-4">
                        <span class="grid size-14 shrink-0 place-items-center rounded-full border border-gold/40 text-gold transition-colors duration-500 group-hover/ph:bg-gold group-hover/ph:text-ink">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1z" />
                            </svg>
                        </span>
                        <span>
                            <span class="block text-[0.6rem] font-semibold tracking-[0.2em] text-cream/58 uppercase">The kitchen</span>
                            <span class="font-display text-2xl text-cream">{{ $mc['phone'] }}</span>
                        </span>
                    </a>
                    <a href="{{ $mc['email_href'] }}" class="link-underline mt-6 block text-sm break-all text-cream/65">{{ $mc['email'] }}</a>
                    <a href="{{ $mc['whatsapp_href'] }}" target="_blank" rel="noopener noreferrer"
                       class="mt-5 inline-flex items-center gap-3 rounded-full border border-gold/40 py-2.5 pr-5 pl-3 text-[0.68rem] font-bold tracking-[0.2em] text-gold uppercase transition-colors hover:bg-gold hover:text-ink">
                        <svg viewBox="0 0 24 24" class="size-4" fill="currentColor" aria-hidden="true">
                            <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 1.8a8.2 8.2 0 1 1-4.2 15.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 0 1 12 3.8m-3.2 4c-.2 0-.5 0-.7.4-.2.3-.9.9-.9 2.1s.9 2.4 1 2.6c.1.2 1.7 2.7 4.2 3.7 2.1.8 2.5.7 2.9.6.5 0 1.4-.5 1.6-1.1.2-.6.2-1 .1-1.1l-.6-.3-1.5-.7c-.2 0-.4-.1-.5.1l-.7.9c-.2.2-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.5 7.5 0 0 1-1.3-1.7c-.2-.3 0-.4.1-.5l.4-.5.2-.4v-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4z" />
                        </svg>
                        WhatsApp us
                    </a>
                </div>

                <div>
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Speak to the team
                    </span>
                    <ul class="mt-6 space-y-3.5">
                        @foreach ($mc['contacts'] as $c)
                            <li>
                                <a href="{{ $c['href'] }}" class="group/c flex items-baseline justify-between gap-4 border-b border-cream/10 pb-3 transition-colors hover:text-gold">
                                    <span class="text-sm text-cream/70 group-hover/c:text-gold">{{ $c['name'] }}</span>
                                    <span class="font-display text-base text-cream tabular-nums group-hover/c:text-gold">{{ $c['phone'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-[0.72rem] leading-relaxed text-cream/58">{{ $mc['supervision'] }}. All catering is {{ $mc['halal'] }}.</p>
                </div>

                <div>
                    <span class="eyebrow flex items-center gap-3.5 text-gold">
                        <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Find the kitchen
                    </span>
                    <address class="mt-6 text-cream/70 not-italic">
                        {{ $mc['address']['line1'] }}<br>
                        {{ $mc['address']['line2'] }}<br>
                        {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}
                    </address>
                    <p class="mt-4 text-sm text-cream/58">Contact hours — {{ $mc['contact_hours'] }}</p>
                </div>
            </aside>
        </div>
    </section>

    @include('site.partials.map')
@endsection
