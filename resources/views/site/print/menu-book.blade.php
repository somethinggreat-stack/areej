@extends('site.print.layout')

@section('title', 'Menu book')

@php
    $mc = config('midland');
    $sections = collect(config('menu'))->keyBy('id');
    $photo = fn (string $name): string => asset('img/'.$name.'.jpg');
    $dietaryKey = ['V' => 'Vegetarian', 'Milk' => 'Contains milk', 'Nuts' => 'Contains nuts', 'Gluten' => 'Contains gluten', 'Fish' => 'Contains fish'];
@endphp

@push('styles')
    /* ---------- Cover ---------- */
    .cover { align-items: center; text-align: center; }
    .cover__photo { position: absolute; left: 0; right: 0; bottom: 0; height: 46%; }
    .cover__photo img { width: 100%; height: 100%; object-fit: cover; opacity: .55; }
    .cover__photo::after { content: ""; position: absolute; inset: 0; background: linear-gradient(var(--navy) 0%, rgba(5, 20, 54, .55) 45%, rgba(5, 20, 54, .92) 100%); }
    .cover__body { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; padding-top: 30mm; }
    .cover__logo { width: 62mm; height: 62mm; border-radius: 50%; box-shadow: 0 0 0 1mm var(--gold), 0 0 0 3mm rgba(232, 172, 38, .25), 0 18mm 30mm rgba(0, 0, 0, .45); }
    .cover__name { margin-top: 13mm; font: 300 40pt/1 var(--display); letter-spacing: .04em; }
    .cover__city { margin-top: 4mm; font: 600 9pt var(--sans); letter-spacing: .6em; text-transform: uppercase; color: var(--gold); padding-left: .6em; }
    .cover__menu { margin-top: 9mm; font: italic 300 30pt/1 var(--display); color: var(--cream); }
    .cover__tag { margin-top: 5mm; font: italic 400 12pt var(--display); color: var(--gold-lit); }
    .cover__base { position: absolute; z-index: 1; left: 0; right: 0; bottom: 19mm; text-align: center; }
    .cover__base p { font: 600 7.5pt var(--sans); letter-spacing: .3em; text-transform: uppercase; color: var(--cream); }
    .cover__base p + p { margin-top: 2.4mm; font: italic 400 9.5pt var(--display); letter-spacing: .02em; text-transform: none; color: rgba(244, 238, 226, .8); }

    /* ---------- Inner pages ---------- */
    .inner { flex: 1; display: flex; flex-direction: column; padding: 8mm 14mm 6mm; min-height: 0; }
    .head { display: flex; align-items: flex-end; gap: 5mm; margin-bottom: 5mm; }
    .head__n { font: 300 34pt/0.8 var(--display); color: var(--gold-deep); }
    .head__title { font: 400 25pt/1 var(--display); color: var(--navy); }
    .head__sub { margin-top: 1.6mm; }
    .head__rule { flex: 1; height: 0.3mm; margin-bottom: 2.4mm; background: linear-gradient(90deg, var(--gold-deep), rgba(184, 129, 26, 0)); }
    .blurb { margin: -2mm 0 5mm; max-width: 135mm; font: italic 400 9.5pt/1.5 var(--display); color: rgba(3, 8, 28, .7); }

    .sub-title { display: flex; align-items: center; gap: 3mm; margin: 5mm 0 2mm; font: 400 15pt var(--display); color: var(--navy); }
    .sub-title::after { content: ""; flex: 1; height: 0.25mm; background: rgba(184, 129, 26, .45); }
    .sub-title span { font: 600 6.5pt var(--sans); letter-spacing: .26em; text-transform: uppercase; color: var(--gold-deep); }

    .dishes { list-style: none; display: grid; grid-template-columns: repeat(var(--cols), 1fr); column-gap: 9mm; }
    .dish { padding: 2.1mm 0 2.2mm; border-bottom: 0.2mm dotted rgba(3, 8, 28, .22); break-inside: avoid; }
    .dish__name { font: 500 11pt/1.2 var(--display); color: var(--ink); }
    .dish__sig { margin-left: 1mm; font-size: 7pt; color: var(--gold-deep); vertical-align: 1.4pt; }
    .dish__tag { margin-left: 1.2mm; font: 700 5.8pt var(--sans); letter-spacing: .08em; color: var(--gold-ink); border: 0.2mm solid rgba(184, 129, 26, .55); border-radius: 99px; padding: 0.2mm 1.3mm; vertical-align: 1.5pt; }
    .dish__desc { margin-top: 0.6mm; font-size: 7.8pt; color: rgba(3, 8, 28, .64); }

    .row { display: grid; gap: 4mm; }
    .key { margin-top: auto; padding-top: 3mm; display: flex; flex-wrap: wrap; gap: 1.5mm 5mm; font-size: 6.6pt; color: rgba(3, 8, 28, .6); }
    .key b { color: var(--gold-ink); }

    /* ---------- Story ---------- */
    .story { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 9mm; }
    .story h1 { font: 300 26pt/1.08 var(--display); color: var(--navy); margin: 2mm 0 4mm; }
    .story h1 em { font-weight: 400; color: var(--gold-deep); }
    .story p.body { font-size: 9pt; line-height: 1.62; color: rgba(3, 8, 28, .78); }
    .story p.body + p.body { margin-top: 3mm; }
    .story p.body:first-of-type::first-letter { float: left; font: 400 30pt/0.82 var(--display); color: var(--gold-deep); margin: 1mm 2mm 0 0; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 5mm; border-top: 0.3mm solid var(--gold-deep); border-bottom: 0.3mm solid var(--gold-deep); }
    .stats div { padding: 3.5mm 0; text-align: center; }
    .stats div + div { border-left: 0.2mm solid rgba(184, 129, 26, .4); }
    .stats strong { display: block; font: 400 20pt/1 var(--display); color: var(--navy); }
    .stats span { font: 600 6pt var(--sans); letter-spacing: .2em; text-transform: uppercase; color: var(--gold-ink); }
    .lists { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; margin-top: 5mm; padding: 4.5mm 6mm; background: var(--navy); color: var(--cream); box-shadow: inset 0 0 0 0.35mm var(--gold), inset 0 0 0 1.4mm var(--navy), inset 0 0 0 1.7mm rgba(232, 172, 38, .45); }
    .lists h3 { margin-bottom: 2mm; font: 600 6.5pt var(--sans); letter-spacing: .24em; text-transform: uppercase; color: var(--gold); }
    .lists li { list-style: none; padding: 0.8mm 0; font-size: 8pt; color: rgba(244, 238, 226, .86); }
    .lists li::before { content: "◆"; margin-right: 1.8mm; font-size: 5pt; color: var(--gold); vertical-align: 1pt; }
    .signed { margin-top: 5mm; font: italic 400 10.5pt var(--display); color: var(--navy); }
    .signed span { display: block; margin-top: 0.6mm; font: 600 6.5pt var(--sans); font-style: normal; letter-spacing: .22em; text-transform: uppercase; color: var(--gold-ink); }

    /* ---------- Back cover ---------- */
    .back { padding: 24mm 22mm 20mm; text-align: center; }
    .back > * { position: relative; z-index: 1; }
    .back > .cover__photo { position: absolute; z-index: 0; }
    .back > .frame { position: absolute; }
    .back__logo { width: 30mm; height: 30mm; border-radius: 50%; box-shadow: 0 0 0 0.7mm var(--gold); }
    .back h2 { margin-top: 7mm; font: 300 28pt/1.05 var(--display); }
    .back__call { margin-top: 8mm; }
    .back__call b { display: block; font: 600 7pt var(--sans); letter-spacing: .32em; text-transform: uppercase; color: var(--gold); }
    .back__call strong { display: block; margin-top: 2mm; font: 400 32pt/1 var(--display); }
    .people { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm; margin-top: 9mm; }
    .people div { padding: 3.5mm 2mm; border: 0.25mm solid rgba(232, 172, 38, .45); }
    .people strong { display: block; font: 400 10.5pt var(--display); color: var(--cream); }
    .people span { display: block; margin-top: 1mm; font-size: 8.5pt; color: var(--gold-lit); letter-spacing: .04em; }
    .back__facts { margin-top: 8mm; display: grid; grid-template-columns: repeat(2, 1fr); gap: 6mm; text-align: left; }
    .back__facts h3 { margin-bottom: 2mm; font: 600 6.5pt var(--sans); letter-spacing: .24em; text-transform: uppercase; color: var(--gold); }
    .back__facts li { list-style: none; padding: 0.8mm 0; font-size: 8.2pt; color: rgba(244, 238, 226, .82); }
    .back__facts li::before { content: "◆"; margin-right: 1.8mm; font-size: 5pt; color: var(--gold); vertical-align: 1pt; }
    .back__address { margin-top: 8mm; font-size: 8.5pt; line-height: 1.7; color: rgba(244, 238, 226, .8); }
    .back__note { margin-top: 5mm; font: italic 400 8.5pt var(--display); color: rgba(244, 238, 226, .65); }
@endpush

@section('sheets')
    {{-- 1. Cover --}}
    <section class="sheet sheet--navy cover">
        <div class="cover__photo"><img src="{{ $photo('feast-copper') }}" alt=""></div>
        <div class="frame"><i></i><i></i></div>
        <div class="cover__body">
            <img class="cover__logo" src="{{ asset('img/midland-logo.png') }}" alt="Midland Catering">
            <h1 class="cover__name foil">{{ $mc['name'] }}</h1>
            <p class="cover__city">{{ $mc['city'] }}</p>
            <div class="ornament" style="margin-top: 8mm"><span></span></div>
            <p class="cover__menu">The Menu</p>
            <p class="cover__tag">{{ $mc['tagline'] }}</p>
        </div>
        <div class="cover__base">
            <p>{{ $mc['speciality'] }} · Halal</p>
            <p>{{ $mc['supervision'] }}</p>
        </div>
    </section>

    {{-- 2. Our story --}}
    <section class="sheet">
        @include('site.print.band')
        <div class="inner">
            <div class="story">
                <div>
                    <p class="eyebrow">Our story</p>
                    <h1>Forty-three years of <em>cooking for families</em>.</h1>
                    <p class="body">For more than 43 years, Raja Mahmood Khan has cooked traditional Asian food for the families of Birmingham — at weddings and walimas, at funerals and Khatam Shareef, at birthdays, community functions and office events. Every dish that leaves our kitchen is still cooked under his supervision.</p>
                    <p class="body">Our food is the food of home. Onions browned slowly, whole spices ground in our own kitchen, meat marinated overnight, rice layered and steamed, breads cooked to order. Everything is halal and cooked fresh on the day.</p>
                    <p class="body">Today the kitchen sits behind a blue and gold shopfront on Landor Street in Saltley. From there we cater for twenty guests or two thousand, take same-day and short-notice bookings, and bring the serving staff, waiters, crockery and serving dishes too — so you can hand over the whole event, not just the cooking.</p>
                    <p class="signed">Raja Mahmood Khan<span>Founder · Midland Catering</span></p>
                </div>
                <div>
                    <figure class="pic pic--framed" style="height: 88mm">
                        <img src="{{ $photo('midland-premises-portrait') }}" alt="The Midland Catering kitchen on Landor Street">
                        <figcaption>Our kitchen, Landor Street, Saltley</figcaption>
                    </figure>
                    <figure class="pic pic--framed" style="height: 40mm; margin-top: 5mm">
                        <img src="{{ $photo('client-chafing-curry') }}" alt="Chafing dishes ready to serve">
                    </figure>
                </div>
            </div>

            <div class="stats">
                <div><strong>43+</strong><span>Years in the trade</span></div>
                <div><strong>20 – 2,000</strong><span>Guests catered</span></div>
                <div><strong>100%</strong><span>Halal</span></div>
            </div>

            <div class="lists">
                <div>
                    <h3>We cater for</h3>
                    <ul>
                        @foreach (['Weddings', 'Parties & Birthdays', 'Functions', 'Funerals', 'Khatam Shareef', 'Corporate Events', 'Business & Home Events'] as $occasion)
                            <li>{{ $occasion }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3>With every order</h3>
                    <ul>
                        @foreach ($mc['included'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3>Also available</h3>
                    <ul>
                        @foreach ($mc['extras'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @include('site.print.foot', ['page' => '02'])
    </section>

    {{-- 3. Appetisers & starters --}}
    <section class="sheet">
        @include('site.print.band')
        <div class="inner">
            <div class="head">
                <span class="head__n">01</span>
                <div>
                    <h2 class="head__title">Appetisers &amp; Starters</h2>
                    <p class="eyebrow head__sub">To open the table · from the grill and the fryer</p>
                </div>
                <span class="head__rule"></span>
            </div>

            <div class="row" style="grid-template-columns: 1.3fr 1fr 1fr; height: 50mm">
                <figure class="pic pic--framed"><img src="{{ $photo('tikka-skewers-grill') }}" alt="Chicken tikka boti over the coals"><figcaption>Chicken Tikka Boti</figcaption></figure>
                <figure class="pic pic--framed"><img src="{{ $photo('chana-chaat') }}" alt="Chana chaat"><figcaption>Chana Chaat</figcaption></figure>
                <figure class="pic pic--framed"><img src="{{ $photo('seekh-kebab-platter') }}" alt="Sheesh kebabs"><figcaption>Chicken Sheesh Kebab</figcaption></figure>
            </div>

            <h3 class="sub-title">Appetisers <span>{{ $sections['appetisers']['sub'] }}</span></h3>
            @include('site.print.dishes', ['dishes' => $sections['appetisers']['dishes'], 'columns' => 3])

            <h3 class="sub-title">Starters <span>{{ $sections['starters']['sub'] }}</span></h3>
            @include('site.print.dishes', ['dishes' => $sections['starters']['dishes']])

            <p class="key">
                <span><b>◆</b> Signature</span>
                @foreach ($dietaryKey as $code => $label)
                    <span><b>{{ $code }}</b> {{ $label }}</span>
                @endforeach
                <span>Starters can be ordered for fewer than 20 guests.</span>
            </p>
        </div>
        @include('site.print.foot', ['page' => '03'])
    </section>

    {{-- 4. Mains --}}
    <section class="sheet">
        @include('site.print.band')
        <div class="inner">
            <div class="head">
                <span class="head__n">02</span>
                <div>
                    <h2 class="head__title">Mains</h2>
                    <p class="eyebrow head__sub">{{ $sections['mains']['sub'] }}</p>
                </div>
                <span class="head__rule"></span>
            </div>
            <p class="blurb">{{ $sections['mains']['blurb'] }}</p>

            <div class="row" style="grid-template-columns: 68mm 1fr; gap: 9mm; flex: 1; min-height: 0">
                <div class="row" style="grid-template-rows: 1.25fr 1fr 1fr; gap: 5mm">
                    <figure class="pic pic--framed"><img src="{{ $photo('karahi-naan') }}" alt="Meat masala with naan"><figcaption>Meat Masala</figcaption></figure>
                    <figure class="pic pic--framed"><img src="{{ $photo('butter-chicken-copper') }}" alt="Butter chicken"><figcaption>Butter Chicken</figcaption></figure>
                    <figure class="pic pic--framed"><img src="{{ $photo('naan-dal') }}" alt="Shahi daal with roti"><figcaption>Shahi Daal</figcaption></figure>
                </div>
                <div>
                    @include('site.print.dishes', ['dishes' => $sections['mains']['dishes'], 'columns' => 1])
                </div>
            </div>

            <p class="key">
                <span><b>◆</b> Signature</span>
                @foreach ($dietaryKey as $code => $label)
                    <span><b>{{ $code }}</b> {{ $label }}</span>
                @endforeach
            </p>
        </div>
        @include('site.print.foot', ['page' => '04'])
    </section>

    {{-- 5. Sides: rice & breads --}}
    <section class="sheet">
        @include('site.print.band')
        <div class="inner">
            <div class="head">
                <span class="head__n">03</span>
                <div>
                    <h2 class="head__title">Sides · Rice &amp; Breads</h2>
                    <p class="eyebrow head__sub">{{ $sections['sides']['sub'] }}</p>
                </div>
                <span class="head__rule"></span>
            </div>
            <p class="blurb">{{ $sections['sides']['blurb'] }}</p>

            <div class="row" style="grid-template-columns: 1.6fr 1fr; height: 82mm">
                <figure class="pic pic--framed"><img src="{{ $photo('biryani-claypot') }}" alt="Biryani in a clay handi"><figcaption>Biryani</figcaption></figure>
                <div class="row" style="grid-template-rows: 1fr 1fr">
                    <figure class="pic pic--framed"><img src="{{ $photo('biryani-platter-red') }}" alt="Meat pilau rice"><figcaption>Meat Pilau Rice</figcaption></figure>
                    <figure class="pic pic--framed"><img src="{{ $photo('pulao-chicken') }}" alt="Chicken pilau rice"><figcaption>Chicken Pilau Rice</figcaption></figure>
                </div>
            </div>

            <h3 class="sub-title">Rice <span>Aged basmati</span></h3>
            @include('site.print.dishes', ['dishes' => array_slice($sections['sides']['dishes'], 0, 6)])

            <h3 class="sub-title">Breads <span>Cooked to order</span></h3>
            @include('site.print.dishes', ['dishes' => array_slice($sections['sides']['dishes'], 6), 'columns' => 3])

            <p class="key">
                <span><b>◆</b> Signature</span>
                @foreach ($dietaryKey as $code => $label)
                    <span><b>{{ $code }}</b> {{ $label }}</span>
                @endforeach
                <span>Roti or naan, salad and mint &amp; chilli sauce come with every order.</span>
            </p>
        </div>
        @include('site.print.foot', ['page' => '05'])
    </section>

    {{-- 6. Desserts & teas --}}
    <section class="sheet">
        @include('site.print.band')
        <div class="inner">
            <div class="head">
                <span class="head__n">04</span>
                <div>
                    <h2 class="head__title">Desserts &amp; Teas</h2>
                    <p class="eyebrow head__sub">To finish · poured all evening</p>
                </div>
                <span class="head__rule"></span>
            </div>

            <div class="row" style="grid-template-columns: 1.25fr 1fr 1fr; grid-template-rows: 1fr 1fr; height: 70mm">
                <figure class="pic pic--framed" style="grid-row: span 2"><img src="{{ $photo('halwa-silver') }}" alt="Gajrella in a silver dish"><figcaption>Gajrella</figcaption></figure>
                <figure class="pic pic--framed"><img src="{{ $photo('gulab-jamun-spoon') }}" alt="Gulab jamun"><figcaption>Gulab Jamon</figcaption></figure>
                <figure class="pic pic--framed"><img src="{{ $photo('rasmalai-plates') }}" alt="Rasmalai"><figcaption>Rasmalai</figcaption></figure>
                <figure class="pic pic--framed" style="grid-column: span 2"><img src="{{ $photo('kheer-clay-pot') }}" alt="Kheer in a clay pot" style="object-position: center 40%"><figcaption>Kheer</figcaption></figure>
            </div>

            <h3 class="sub-title">Desserts <span>{{ $sections['desserts']['sub'] }}</span></h3>
            @include('site.print.dishes', ['dishes' => $sections['desserts']['dishes']])

            <div class="row" style="grid-template-columns: 1fr 1.9fr; gap: 8mm; margin-top: 2mm">
                <div class="row" style="grid-template-columns: 1fr 1fr; height: 40mm; margin-top: 5mm">
                    <figure class="pic pic--framed"><img src="{{ $photo('pink-tea') }}" alt="Kashmiri pink tea"></figure>
                    <figure class="pic pic--framed"><img src="{{ $photo('chai-pour') }}" alt="Desi tea being poured"></figure>
                </div>
                <div>
                    <h3 class="sub-title">Teas <span>{{ $sections['drinks']['sub'] }}</span></h3>
                    @include('site.print.dishes', ['dishes' => $sections['drinks']['dishes']])
                </div>
            </div>

            <p class="key">
                <span><b>◆</b> Signature</span>
                @foreach ($dietaryKey as $code => $label)
                    <span><b>{{ $code }}</b> {{ $label }}</span>
                @endforeach
            </p>
        </div>
        @include('site.print.foot', ['page' => '06'])
    </section>

    {{-- 7. Back cover --}}
    <section class="sheet sheet--navy back">
        <div class="cover__photo" style="height: 34%"><img src="{{ $photo('mixed-grill-platter') }}" alt=""></div>
        <div class="frame"><i></i><i></i></div>
        <img class="back__logo" src="{{ asset('img/midland-logo.png') }}" alt="" style="margin: 0 auto">
        <h2 class="foil">Book your event</h2>
        <div class="ornament" style="margin-top: 5mm"><span></span></div>

        <p class="back__call"><b>For inquiries, call</b><strong>{{ $mc['phone'] }}</strong></p>

        <div class="people">
            @foreach ($mc['contacts'] as $contact)
                <div><strong>{{ $contact['name'] }}</strong><span>{{ $contact['phone'] }}</span></div>
            @endforeach
        </div>

        <div class="back__facts">
            <div>
                <h3>With every order</h3>
                <ul>
                    @foreach ($mc['included'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h3>Also available</h3>
                <ul>
                    @foreach ($mc['extras'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <p class="back__address">
            {{ $mc['address']['line1'] }}, {{ $mc['address']['line2'] }}, {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }}<br>
            {{ $mc['email'] }} · WhatsApp {{ $mc['whatsapp'] }} · Every day {{ $mc['contact_hours'] }}
        </p>
        <p class="back__note">Halal food · 20 to 2,000 guests · Same-day short notice welcome.<br>Every event is priced on guest numbers. Please tell us about any allergies when you book.</p>
    </section>
@endsection
