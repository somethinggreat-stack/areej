<?php

/**
 * Verified business facts for Midland Catering.
 *
 * Everything here was confirmed by the client. Nothing is assumed. See
 * docs/CLIENT-REQUIREMENTS.md for the source of each value.
 */
return [
    'name' => 'Midland Catering',
    'legal_name' => 'Midland Catering Ltd',
    'company_number' => '09995085',
    'tagline' => 'For Events with a better taste',
    'speciality' => 'Specialists in Traditional Asian Catering',
    'supervision' => 'Under the supervision of Raja Mahmood Khan',
    'city' => 'Birmingham',

    'phone' => '0121 773 4778',
    'phone_href' => 'tel:+441217734778',
    'email' => 'midlandcateringltd@hotmail.com',
    'email_href' => 'mailto:midlandcateringltd@hotmail.com',
    'whatsapp' => '07929 885 106',
    'whatsapp_href' => 'https://wa.me/447929885106',

    'address' => [
        'line1' => 'Unit 3, Landor Street',
        'line2' => 'Saltley',
        'city' => 'Birmingham',
        'postcode' => 'B8 1AG',
    ],

    'geo' => ['lat' => 52.4817557, 'lng' => -1.8757983],
    'place_id' => 'ChIJLSYYNYC8cEgRGdinLqYovTM',
    'maps_url' => 'https://www.google.com/maps/place/?q=place_id:ChIJLSYYNYC8cEgRGdinLqYovTM',

    'google' => [
        'rating' => 4.2,
        'review_count' => 156,
        'reviews_url' => 'https://search.google.com/local/reviews?placeid=ChIJLSYYNYC8cEgRGdinLqYovTM',
        'write_review_url' => 'https://search.google.com/local/writereview?placeid=ChIJLSYYNYC8cEgRGdinLqYovTM',
    ],

    // The client corrected this: halal, but NOT HMC certified.
    'halal' => 'Halal',

    'guests' => [
        'min' => 20,
        'max' => 2000,
        'label' => '20 – 2,000 guests',
        'note' => 'Starters can be ordered for fewer than 20 guests.',
    ],

    'contact_hours' => '9:00am – 8:00pm',

    'contacts' => [
        ['name' => 'Raja Mahmood Khan', 'phone' => '07976 289 686', 'href' => 'tel:+447976289686'],
        ['name' => 'Azram Khan', 'phone' => '07929 885 106', 'href' => 'tel:+447929885106'],
        ['name' => 'Kabir Kayani', 'phone' => '07568 368 686', 'href' => 'tel:+447568368686'],
    ],

    'credentials' => [
        'Halal',
        '20 to 2,000 guests',
        'Same-day short notice',
        'Collection, delivery & serving',
    ],

    'included' => [
        'Roti or naan',
        'Salad',
        'Mint & chilli sauce',
        'Delivery',
        'Serving dishes',
    ],

    'extras' => [
        'Collection, delivery and serving',
        'Venue hire available',
        'Kitchen serving staff available',
        'Waiter service available',
        'Cutlery & crockery hire available',
    ],

    /**
     * Empty until the client supplies real profile links — their Facebook,
     * Instagram and TikTok are not properly set up yet, and a link that goes
     * nowhere looks worse than no link.
     */
    'socials' => [],

    'dietary_key' => [
        ['code' => 'V', 'label' => 'Suitable for vegetarians'],
        ['code' => 'Milk', 'label' => 'Contains milk'],
        ['code' => 'Nuts', 'label' => 'Contains nuts'],
        ['code' => 'Gluten', 'label' => 'Contains gluten'],
        ['code' => 'Fish', 'label' => 'Contains fish'],
    ],
];
