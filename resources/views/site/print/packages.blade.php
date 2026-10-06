@extends('site.print.layout')

@section('title', $packageMenu['title'])

@php
    $mc = config('midland');
    $photo = fn (string $name): string => asset('img/'.$name.'.jpg');
@endphp

@push('styles')
    .hero { position: relative; height: 56mm; }
    .hero img { width: 100%; height: 100%; object-fit: cover; }
    .hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(5, 20, 54, .94) 0%, rgba(5, 20, 54, .72) 48%, rgba(5, 20, 54, .12) 100%); }
    .hero__text { position: absolute; z-index: 1; left: 14mm; top: 0; bottom: 0; width: 120mm; display: flex; flex-direction: column; justify-content: center; color: var(--cream); }
    .hero__text h1 { margin-top: 2.5mm; font: 300 30pt/1.02 var(--display); }
    .hero__text p { margin-top: 3.5mm; font-size: 8.6pt; line-height: 1.55; color: rgba(244, 238, 226, .82); }

    .packs { flex: 1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 5mm; padding: 9mm 12mm 7mm; min-height: 0; }
    .pack { position: relative; display: flex; flex-direction: column; padding: 7mm 5mm 5mm; background: #fff; box-shadow: 0 0 0 0.3mm rgba(184, 129, 26, .6), 0 0 0 1.3mm #fff, 0 0 0 1.6mm rgba(184, 129, 26, .35); }
    .pack--featured { background: var(--navy); color: var(--cream); box-shadow: 0 0 0 0.45mm var(--gold), 0 0 0 1.5mm var(--navy), 0 0 0 1.85mm rgba(232, 172, 38, .6); }
    .pack__no { position: absolute; top: -4.5mm; left: 50%; transform: translateX(-50%); padding: 1.3mm 4.5mm; background: var(--foil); color: var(--ink); font: 700 6.6pt var(--sans); letter-spacing: .24em; text-transform: uppercase; white-space: nowrap; }
    .pack__big { text-align: center; font: 300 32pt/1 var(--display); color: var(--gold-deep); }
    .pack--featured .pack__big { color: var(--gold); }
    .pack__name { margin-top: 1.5mm; text-align: center; font: 400 15pt/1.1 var(--display); }
    .pack .ornament { margin: 3mm 0 3mm; }
    .pack .ornament::before, .pack .ornament::after { width: 12mm; }
    .course { margin-top: 2mm; }
    .course h3 { font: 600 6pt var(--sans); letter-spacing: .22em; text-transform: uppercase; color: var(--gold-ink); }
    .pack--featured .course h3 { color: var(--gold); }
    .course li { list-style: none; font: 400 10pt/1.35 var(--display); }
    .pack__price { margin-top: auto; padding-top: 4mm; text-align: center; border-top: 0.25mm dotted rgba(184, 129, 26, .6); }
    .pack__price strong { display: block; font: 400 15pt var(--display); }
    .pack__price span { font: 600 6pt var(--sans); letter-spacing: .2em; text-transform: uppercase; color: rgba(3, 8, 28, .55); }
    .pack--featured .pack__price span { color: rgba(244, 238, 226, .6); }

    .how { display: grid; grid-template-columns: repeat(3, 1fr) 1.2fr; gap: 4mm; margin: 0 12mm 6mm; padding: 4mm 5mm; border-top: 0.3mm solid var(--gold-deep); border-bottom: 0.3mm solid var(--gold-deep); }
    .how div { display: flex; gap: 2.5mm; align-items: flex-start; font-size: 7.8pt; color: rgba(3, 8, 28, .75); }
    .how b { font: 300 17pt/0.9 var(--display); color: var(--gold-deep); }
    .how .note { display: block; font: italic 400 8.5pt/1.4 var(--display); color: var(--navy); }
@endpush

@section('sheets')
    <section class="sheet">
        @include('site.print.band')

        <div class="hero">
            <img src="{{ $photo($packageMenu['image']) }}" alt="">
            <div class="hero__text">
                <p class="eyebrow">{{ $packageMenu['occasions'] }}</p>
                <h1 class="foil">{{ $packageMenu['title'] }}</h1>
                <p>{{ $packageMenu['lede'] }}</p>
            </div>
        </div>

        <div class="packs">
            @foreach ($packageMenu['packages'] as $package)
                <article @class(['pack', 'pack--featured' => $loop->iteration === 2])>
                    <span class="pack__no">Package No. {{ $loop->iteration }}</span>
                    <p class="pack__big">{{ $loop->iteration }}</p>
                    <h2 class="pack__name">{{ $package['name'] }}</h2>
                    <div class="ornament"><span></span></div>

                    @foreach ($package['courses'] as $course => $dishes)
                        <div class="course">
                            <h3>{{ $course }}</h3>
                            <ul>
                                @foreach ($dishes as $dish)
                                    <li>{{ $dish }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    <p class="pack__price">
                        <strong>{{ $package['price'] ?? 'Price on request' }}</strong>
                        <span>{{ $package['price'] ? $packageMenu['unit'] : 'Call with your numbers' }}</span>
                    </p>
                </article>
            @endforeach
        </div>


        <div class="how">
            <div><b>1</b><span>Choose a package number.</span></div>
            <div><b>2</b><span>Tell us how many people and the date.</span></div>
            <div><b>3</b><span>We give you one price for everything.</span></div>
            <div><span class="note">Roti or naan, salad and mint &amp; chilli sauce come with every package. Extra dishes can be added to any package.</span></div>
        </div>

        @include('site.print.foot')
    </section>
@endsection
