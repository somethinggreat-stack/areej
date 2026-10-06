@php
    $mc = config('midland');
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Print pieces are handed out on paper and as PDFs; keep them out of search until the client signs them off. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — {{ $mc['name'] }}</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    {{ Vite::fonts(['fraunces', 'inter']) }}
    <style>
        :root {
            --ink: #03081c;
            --navy: #0a2b8f;
            --navy-2: #071c4a;
            --royal: #0b3fc6;
            --royal-deep: #062a8c;
            --gold: #f2b822;
            --gold-lit: #ffd75e;
            --gold-deep: #b8811a;
            --gold-ink: #7a5410;
            --cream: #f4eee2;
            --paper: #fbf7ef;
            --sand: #e5dbc8;
            --foil: linear-gradient(115deg, #b8811a 0%, #f6cd6a 38%, #e8ac26 55%, #fbe3a1 70%, #b8811a 100%);
            --display: "Fraunces", "Times New Roman", serif;
            --sans: "Inter", ui-sans-serif, system-ui, sans-serif;
        }

        @page { size: A4; margin: 0; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            background: #1b2033;
            color: var(--ink);
            font-family: var(--sans);
            font-size: 9.5pt;
            line-height: 1.45;
        }

        /* ---------- Screen toolbar ---------- */
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px;
            padding: 12px 16px;
            background: rgba(3, 8, 28, .92);
            color: var(--cream);
            font-size: 13px;
        }
        .toolbar a, .toolbar button {
            border: 1px solid rgba(232, 172, 38, .5);
            border-radius: 999px;
            padding: 7px 16px;
            background: none;
            color: var(--gold);
            font: 600 12px var(--sans);
            letter-spacing: .08em;
            text-decoration: none;
            text-transform: uppercase;
            cursor: pointer;
        }
        .toolbar button { background: var(--gold); color: var(--ink); }
        .toolbar a[aria-current] { background: rgba(232, 172, 38, .15); }

        /* ---------- The A4 sheet ---------- */
        .sheet {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 24px auto;
            overflow: hidden;
            background: var(--paper);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
            display: flex;
            flex-direction: column;
        }

        @media print {
            body { background: none; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; break-after: page; page-break-after: always; }
            .sheet:last-child { break-after: auto; page-break-after: auto; }
        }

        @media screen and (max-width: 840px) {
            .sheet { zoom: .46; margin: 12px auto; }
        }

        .sheet--navy {
            background:
                radial-gradient(120% 70% at 50% 0%, rgba(42, 99, 240, .55) 0%, transparent 60%),
                radial-gradient(90% 60% at 50% 110%, rgba(232, 172, 38, .14) 0%, transparent 60%),
                var(--navy);
            color: var(--cream);
        }

        /* Fine double gold rule framing a page. */
        .frame { position: absolute; inset: 8mm; border: 0.35mm solid rgba(232, 172, 38, .75); pointer-events: none; }
        .frame::after { content: ""; position: absolute; inset: 1.4mm; border: 0.15mm solid rgba(232, 172, 38, .45); }
        .frame i { position: absolute; width: 3.2mm; height: 3.2mm; background: var(--gold); transform: rotate(45deg); }
        .frame i:nth-child(1) { top: -1.6mm; left: 50%; margin-left: -1.6mm; }
        .frame i:nth-child(2) { bottom: -1.6mm; left: 50%; margin-left: -1.6mm; }

        .foil {
            background: var(--foil);
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
        }

        .eyebrow {
            font: 600 7pt var(--sans);
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--gold-deep);
        }
        .sheet--navy .eyebrow { color: var(--gold); }

        .ornament { display: flex; align-items: center; justify-content: center; gap: 3mm; color: var(--gold); }
        .ornament::before, .ornament::after { content: ""; height: 0.3mm; width: 22mm; background: currentColor; opacity: .7; }
        .ornament span { width: 2.4mm; height: 2.4mm; border: 0.35mm solid currentColor; transform: rotate(45deg); }

        /* ---------- Shared header band (same on every inner page) ---------- */
        .band {
            position: relative;
            display: flex; align-items: center; justify-content: space-between; gap: 6mm;
            padding: 6mm 14mm 5mm;
            background: var(--navy);
            color: var(--cream);
            border-bottom: 0.8mm solid var(--gold);
        }
        .band::after { content: ""; position: absolute; left: 0; right: 0; bottom: -2mm; height: 0.3mm; background: rgba(232, 172, 38, .55); }
        .band__brand { display: flex; align-items: center; gap: 4mm; }
        .band__brand img { width: 15mm; height: 15mm; border-radius: 50%; box-shadow: 0 0 0 0.5mm var(--gold); }
        .band__name { font: 400 17pt/1 var(--display); letter-spacing: .02em; }
        .band__name small { display: block; margin-top: 1.4mm; font: 600 6.5pt var(--sans); letter-spacing: .32em; text-transform: uppercase; color: var(--gold); }
        .band__right { text-align: right; }
        .band__right .tag { font: italic 300 10pt var(--display); color: var(--gold-lit); }
        .band__super { font: 700 7pt var(--sans); letter-spacing: .2em; text-transform: uppercase; color: var(--gold); margin-bottom: 1.2mm; }
        .band__right .halal { margin-top: 1.2mm; font: 600 6.5pt var(--sans); letter-spacing: .24em; text-transform: uppercase; color: rgba(244, 238, 226, .7); }

        /* ---------- Shared footer (same on every inner page) ---------- */
        .foot {
            margin-top: auto;
            padding: 4mm 14mm 5mm;
            background: var(--navy);
            color: rgba(244, 238, 226, .78);
            border-top: 0.8mm solid var(--gold);
            font-size: 7.4pt;
        }
        .foot__row { display: flex; justify-content: space-between; align-items: center; gap: 5mm; }
        .foot__call { font: 400 13pt var(--display); color: var(--cream); }
        .foot__call b { display: block; font: 600 6.3pt var(--sans); letter-spacing: .26em; text-transform: uppercase; color: var(--gold); }
        .foot__people { display: flex; gap: 5mm; }
        .foot__people span { display: block; }
        .foot__people strong { display: block; font-weight: 600; color: var(--cream); font-size: 7pt; }
        .foot__line { margin-top: 2.6mm; padding-top: 2.2mm; border-top: 0.2mm solid rgba(232, 172, 38, .3); display: flex; justify-content: space-between; gap: 4mm; font-size: 6.8pt; letter-spacing: .02em; }
        .foot__page { color: var(--gold); font-weight: 600; letter-spacing: .2em; }

        /* ---------- Pictures ---------- */
        .pic { position: relative; min-height: 0; overflow: hidden; background: var(--sand); }
        .pic img { position: absolute; inset: 0; display: block; width: 100%; height: 100%; object-fit: cover; }
        .pic--framed { box-shadow: 0 0 0 0.35mm var(--gold), 0 0 0 1.4mm var(--paper), 0 0 0 1.75mm rgba(184, 129, 26, .5); }
        .pic figcaption {
            position: absolute; left: 0; right: 0; bottom: 0;
            padding: 6mm 3mm 2mm;
            background: linear-gradient(transparent, rgba(3, 8, 28, .82));
            color: var(--cream);
            font: italic 400 8pt var(--display);
        }

        @stack('styles')
    </style>
</head>
<body>
    <nav class="toolbar" aria-label="Print menus">
        <a href="{{ route('site.menu.book') }}" @if (request()->routeIs('site.menu.book')) aria-current="page" @endif>Menu book</a>
        @foreach (config('packages') as $packageMenu)
            <a href="{{ route('site.packages', $packageMenu['slug']) }}" @if (request()->is('packages/'.$packageMenu['slug'])) aria-current="page" @endif>{{ $packageMenu['title'] }}</a>
        @endforeach
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </nav>

    @yield('sheets')
</body>
</html>
