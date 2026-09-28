@php
    $mc = config('midland');
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', $mc['name']) — {{ $mc['city'] }}</title>
    <meta name="description" content="@yield('description', 'Halal Asian event catering in Birmingham. Weddings, functions, funerals, Khatam Shareef and corporate events for 20 to 2,000 guests.')">

    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-icon.png') }}">
    {{-- Self-hosted: no third-party stylesheet in the critical path. --}}
    {{ Vite::fonts(['fraunces', 'inter']) }}

    @vite(['resources/css/site.css', 'resources/js/site.js'])

    @php
        // Built here rather than in an @json directive: Blade would try to
        // compile the '@context' and '@type' keys as directives.
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'FoodEstablishment',
            'additionalType' => 'https://schema.org/CateringService',
            'name' => $mc['legal_name'],
            'alternateName' => $mc['name'],
            'slogan' => $mc['tagline'],
            'telephone' => $mc['phone'],
            'email' => $mc['email'],
            'servesCuisine' => ['Pakistani', 'Indian', 'South Asian', 'Halal'],
            'priceRange' => '££',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $mc['address']['line1'].', '.$mc['address']['line2'],
                'addressLocality' => $mc['address']['city'],
                'postalCode' => $mc['address']['postcode'],
                'addressCountry' => 'GB',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $mc['geo']['lat'],
                'longitude' => $mc['geo']['lng'],
            ],
            'hasMap' => $mc['maps_url'],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $mc['google']['rating'],
                'reviewCount' => $mc['google']['review_count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ];
    @endphp

    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="min-h-svh bg-ink">
    <a href="#main" class="skip-link">Skip to content</a>

    @include('site.partials.loader')
    @include('site.partials.header')
    @include('site.partials.nav')

    <main id="main">
        @yield('content')
    </main>

    @include('site.partials.footer')
    @include('site.partials.chrome')
</body>
</html>
