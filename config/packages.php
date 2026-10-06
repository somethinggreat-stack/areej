<?php

/**
 * The three package menus: each one is a single printed page carrying three
 * numbered packages, so a customer can ring up and say "funeral package
 * No. 2 for 150 people on Friday" and be given one price.
 *
 * DRAFT CONTENT: Areej asked for placeholder dishes until she has agreed the
 * exact items with Azram Khan. Every dish here is taken from config/menu.php.
 * Leave a price as null to print "Price on request"; set it (e.g. '£12.50')
 * to print it on the package.
 */
return [
    [
        'slug' => 'celebrations',
        'title' => 'Celebration Packages',
        'occasions' => 'Weddings · Birthdays · Parties · Functions',
        'lede' => 'Three ready-made menus for the big days. Choose a package, tell us how many guests and the date, and we give you one price for the lot.',
        'image' => 'wedding-long-table',
        'unit' => 'per head',
        'packages' => [
            [
                'name' => 'Classic',
                'price' => null,
                'courses' => [
                    'Starter' => ['Veg Samosa', 'Chicken Wings'],
                    'Main' => ['Chicken Masala'],
                    'Rice' => ['Chicken Pilau Rice'],
                    'Dessert' => ['Gajrella'],
                ],
            ],
            [
                'name' => 'Signature',
                'price' => null,
                'courses' => [
                    'Starters' => ['Chicken Tikka Boti', 'Chicken Sheesh Kebab'],
                    'Mains' => ['Meat Masala', 'Chicken Masala'],
                    'Rice' => ['Meat Pilau Rice'],
                    'Desserts' => ['Gajrella', 'Gulab Jamon'],
                    'Drink' => ['Desi Tea'],
                ],
            ],
            [
                'name' => 'Royal',
                'price' => null,
                'courses' => [
                    'Appetiser' => ['Samosa Chaat'],
                    'Starters' => ['Chicken Tikka Boti', 'Chicken Malai Boti', 'Chops'],
                    'Mains' => ['Meat Masala', 'Butter Chicken', 'Shahi Daal'],
                    'Rice & bread' => ['Meat Biryani', 'Roghni Naan'],
                    'Desserts' => ['Gajrella', 'Rasmalai', 'Ice Cream'],
                    'Drink' => ['Kashmiri Pink Tea'],
                ],
            ],
        ],
    ],
    [
        'slug' => 'funeral-and-khatam-shareef',
        'title' => 'Funeral & Khatam Shareef',
        'occasions' => 'Funerals · Khatam Shareef · Quran Khwani',
        'lede' => 'Simple, familiar food, delivered warm and set out without fuss. Same-day and short-notice bookings are welcome — one phone call is enough.',
        'image' => 'naan-dal',
        'unit' => 'per head',
        'packages' => [
            [
                'name' => 'Simple',
                'price' => null,
                'courses' => [
                    'Rice' => ['Chicken Pilau Rice'],
                    'Main' => ['Shahi Daal'],
                    'Drink' => ['Desi Tea'],
                ],
            ],
            [
                'name' => 'Traditional',
                'price' => null,
                'courses' => [
                    'Rice' => ['Meat Pilau Rice'],
                    'Mains' => ['Chicken Masala', 'Shahi Daal'],
                    'Sweet' => ['Sweet Rice (Zarda)'],
                    'Drink' => ['Desi Tea'],
                ],
            ],
            [
                'name' => 'Complete',
                'price' => null,
                'courses' => [
                    'Rice' => ['Meat Pilau Rice'],
                    'Mains' => ['Meat Masala', 'Chicken Masala', 'Shahi Daal'],
                    'Sweets' => ['Sweet Rice (Zarda)', 'Kheer'],
                    'Drink' => ['Desi Tea'],
                ],
            ],
        ],
    ],
    [
        'slug' => 'daily',
        'title' => 'Daily Packages',
        'occasions' => 'Small gatherings · Office lunches · Family meals',
        'lede' => 'Everyday food in fixed sizes for 20, 30 or 50 people. Ring with the package number and the day, and we will quote you straight away.',
        'image' => 'curry-trio',
        'unit' => 'per package',
        'packages' => [
            [
                'name' => 'For 20 people',
                'price' => null,
                'courses' => [
                    'Main' => ['Chicken Masala'],
                    'Rice' => ['Chicken Pilau Rice'],
                    'Sweet' => ['Kheer'],
                ],
            ],
            [
                'name' => 'For 30 people',
                'price' => null,
                'courses' => [
                    'Starter' => ['Veg Samosa'],
                    'Mains' => ['Meat Masala', 'Shahi Daal'],
                    'Rice' => ['Meat Pilau Rice'],
                    'Sweet' => ['Gajrella'],
                ],
            ],
            [
                'name' => 'For 50 people',
                'price' => null,
                'courses' => [
                    'Starters' => ['Chicken Tikka Boti', 'Veg Samosa'],
                    'Mains' => ['Meat Masala', 'Chicken Masala'],
                    'Rice' => ['Meat Pilau Rice'],
                    'Sweet' => ['Gajrella'],
                    'Drink' => ['Desi Tea'],
                ],
            ],
        ],
    ],
];
