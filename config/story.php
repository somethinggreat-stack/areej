<?php

/**
 * Narrative content for the pinned brand story and the event process timeline.
 * Text confirmed with the client — see docs/CLIENT-REQUIREMENTS.md.
 */
return [

    /**
     * Pinned scroll story. The `tone` drives the background colour the section
     * evolves through as each chapter arrives.
     */
    'chapters' => [
        [
            'id' => 'rooted',
            'kicker' => 'Chapter One',
            'title' => 'Rooted in Birmingham',
            'body' => 'Landor Street, Saltley. A working kitchen behind a blue and gold shopfront that has fed this city’s weddings, funerals and functions for years. We are not a booking platform with a van — we are the people cooking your food.',
            'image' => 'midland-premises',
            'tone' => 'navy',
        ],
        [
            'id' => 'service',
            'kicker' => 'Chapter Two',
            'title' => 'Staff, Crockery, Everything',
            'body' => 'Food is only half of it. Kitchen serving staff, waiter service, cutlery and crockery hire, serving dishes and venue hire are all available — so you can hand over the whole evening rather than just the cooking.',
            'image' => 'client-chafing-curry',
            'tone' => 'char',
        ],
        [
            'id' => 'occasion',
            'kicker' => 'Chapter Three',
            'title' => 'Twenty Guests, or Two Thousand',
            'body' => 'The same kitchen caters a front room in Saltley and a two-thousand-cover function. Build your own menu from our list, tell us the numbers, and we scale it. Same-day short notice is not a problem.',
            'image' => 'wedding-long-table',
            'tone' => 'cream',
        ],
    ],

    /** Background colours per chapter tone. */
    'tones' => [
        'navy' => ['bg' => '#051436', 'text' => '#f4eee2', 'muted' => 'rgba(244,238,226,0.62)',
            'accent' => '#e8ac26',
        ],
        'char' => ['bg' => '#14120f', 'text' => '#f4eee2', 'muted' => 'rgba(244,238,226,0.60)',
            'accent' => '#e8ac26',
        ],
        'royal' => ['bg' => '#062a8c', 'text' => '#f4eee2', 'muted' => 'rgba(244,238,226,0.68)',
            'accent' => '#f6cd6a',
        ],
        'cream' => ['bg' => '#f4eee2', 'text' => '#03081c', 'muted' => 'rgba(3,8,28,0.62)',
            'accent' => '#062a8c',
        ],
    ],

    /** How a booking runs, first call to clear-down. */
    'process' => [
        [
            'n' => '01',
            'title' => 'Tell Us About Your Event',
            'body' => 'Date, venue, guest numbers and the kind of occasion. A five-minute call is usually enough. Same-day and short-notice bookings welcome.',
            'image' => 'guest-dining',
        ],
        [
            'n' => '02',
            'title' => 'Build Your Own Menu',
            'body' => 'Choose your appetisers, starters, mains, rice and breads, desserts and hot drinks from our list. Nothing is a fixed package.',
            'image' => 'curry-trio',
        ],
        [
            'n' => '03',
            'title' => 'We Confirm Every Detail',
            'body' => 'Quantities, timings, allergens, venue access, staff, crockery and serving dishes — all written down and confirmed with you.',
            'image' => 'prep-board',
        ],
        [
            'n' => '04',
            'title' => 'Cooked, Delivered & Served',
            'body' => 'Cooked fresh on the day in our own kitchen, delivered hot in serving dishes, and served to schedule.',
            'image' => 'buffet-chafing',
        ],
        [
            'n' => '05',
            'title' => 'Cleared & Followed Up',
            'body' => 'Our staff clear down and we check in afterwards. Most of our work comes from people we have already fed.',
            'image' => 'long-table-guests',
        ],
    ],

    /**
     * Gallery. Each entry carries a written caption and a category used by the
     * on-page filter — never a bare filename.
     */
    'gallery' => [
        ['file' => 'midland-premises', 'caption' => 'Our kitchen on Landor Street, Saltley', 'category' => 'Food', 'span' => 'wide'],
        ['file' => 'midland-premises-portrait', 'caption' => 'The shopfront, Birmingham B8', 'category' => 'Food', 'span' => 'tall'],
        ['file' => 'client-buffet-service', 'caption' => 'Buffet service on the line', 'category' => 'Corporate', 'span' => null],
        ['file' => 'wedding-long-table', 'caption' => 'Wedding service, Birmingham', 'category' => 'Weddings', 'span' => 'wide'],
        ['file' => 'biryani-platter-red', 'caption' => 'Meat pilau rice, sharing platter', 'category' => 'Food', 'span' => 'tall'],
        ['file' => 'buffet-chafing', 'caption' => 'Buffet line set for service', 'category' => 'Corporate', 'span' => null],
        ['file' => 'tikka-skewers-grill', 'caption' => 'Chicken tikka boti over coals', 'category' => 'Food', 'span' => 'tall'],
        ['file' => 'banquet-hall', 'caption' => 'Walima setup, 400 covers', 'category' => 'Weddings', 'span' => 'wide'],
        ['file' => 'samosa-board', 'caption' => 'Samosa and chutney board', 'category' => 'Food', 'span' => null],
        ['file' => 'conference-hall', 'caption' => 'Conference refreshments', 'category' => 'Corporate', 'span' => 'wide'],
        ['file' => 'balloons', 'caption' => 'Birthday party, Birmingham', 'category' => 'Private', 'span' => null],
        ['file' => 'karahi-naan', 'caption' => 'Meat masala and fresh naan', 'category' => 'Food', 'span' => 'tall'],
        ['file' => 'wedding-table-setting', 'caption' => 'Table dressing and place settings', 'category' => 'Weddings', 'span' => null],
        ['file' => 'mezze-table', 'caption' => 'Sharing table for a family function', 'category' => 'Private', 'span' => 'wide'],
        ['file' => 'butter-chicken-copper', 'caption' => 'Butter chicken in a copper handi', 'category' => 'Food', 'span' => 'wide'],
        ['file' => 'event-glassware', 'caption' => 'Crockery and front-of-house setup', 'category' => 'Corporate', 'span' => 'tall'],
        ['file' => 'halwa-silver', 'caption' => 'Gajrella in silver service', 'category' => 'Food', 'span' => null],
        ['file' => 'long-table-guests', 'caption' => 'Communal seating, home event', 'category' => 'Private', 'span' => 'wide'],
        ['file' => 'mixed-grill-platter', 'caption' => 'Mixed grill starters', 'category' => 'Food', 'span' => null],
        ['file' => 'chicken-wings', 'caption' => 'Chicken wings off the grill', 'category' => 'Food', 'span' => null],
        ['file' => 'client-chafing-curry', 'caption' => 'Chafing dishes ready to serve', 'category' => 'Private', 'span' => null],
        ['file' => 'venue-modern', 'caption' => 'Corporate venue, Birmingham', 'category' => 'Corporate', 'span' => null],
    ],

    'gallery_categories' => ['All', 'Weddings', 'Corporate', 'Private', 'Food'],

    /** Scroll-driven collage on the home page: where each frame flies in from. */
    'collage' => [
        ['file' => 'banquet-hall', 'class' => 'col-span-6 row-span-4 col-start-1 row-start-1', 'x' => -140, 'y' => -70, 'r' => -9, 'depth' => 14],
        ['file' => 'biryani-platter-red', 'class' => 'col-span-4 row-span-5 col-start-7 row-start-1', 'x' => 130, 'y' => -100, 'r' => 8, 'depth' => -18],
        ['file' => 'client-buffet-service', 'class' => 'col-span-3 row-span-3 col-start-11 row-start-2', 'x' => 160, 'y' => 60, 'r' => 11, 'depth' => 22],
        ['file' => 'wedding-table-setting', 'class' => 'col-span-4 row-span-4 col-start-1 row-start-5', 'x' => -150, 'y' => 90, 'r' => 7, 'depth' => -14],
        ['file' => 'mixed-grill-platter', 'class' => 'col-span-3 row-span-3 col-start-5 row-start-5', 'x' => 0, 'y' => 150, 'r' => -6, 'depth' => 10],
        ['file' => 'event-glassware', 'class' => 'col-span-5 row-span-3 col-start-8 row-start-6', 'x' => 120, 'y' => 120, 'r' => -8, 'depth' => -20],
    ],
];
