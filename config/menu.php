<?php

/**
 * Midland Catering's real menu, transcribed from their own menu board:
 * 48 dishes across 6 sections. No prices — every event is quoted on numbers.
 */
return [
    [
        'id' => 'appetisers',
        'n' => '01',
        'title' => 'Appetisers',
        'sub' => 'To open the table',
        'blurb' => 'Chaat plates to start the meal — sharp, tangy and gone before the mains arrive.',
        'image' => 'chana-chaat',
        'accent' => 'samosa-street-plate',
        'dishes' => [
            [
                'name' => 'Samosa Chaat',
                'desc' => 'Crushed samosa with chickpeas, yoghurt and chutneys',
                'tags' => [
                    'V',
                ],
                'signature' => true,
            ],
            [
                'name' => 'Chana Chaat',
                'desc' => 'Spiced chickpeas with onion, tomato and tamarind',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Papri Chaat',
                'desc' => 'Crisp wafers, potato, yoghurt and sweet chutney',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
        ],
    ],
    [
        'id' => 'starters',
        'n' => '02',
        'title' => 'Starters',
        'sub' => 'From the grill and the fryer',
        'blurb' => 'Marinated, grilled and fried to order. Starters can be ordered on their own for fewer than twenty guests.',
        'image' => 'tandoori-platter',
        'accent' => 'seekh-kebab-platter',
        'dishes' => [
            [
                'name' => 'Roast Drumstick',
                'desc' => 'Marinated chicken drumsticks roasted until the skin crisps',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Chicken Tikka Boti',
                'desc' => 'Boneless chicken in a red spice marinade, char-grilled',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Chicken Malai Boti',
                'desc' => 'Cream marinade, mild and tender',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chicken Wings',
                'desc' => 'Marinated overnight and finished over high heat',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chicken Sheesh Kebab',
                'desc' => 'Minced chicken with onion, coriander and green chilli',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Lamb Boti',
                'desc' => 'Lamb pieces marinated and grilled on the skewer',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chops',
                'desc' => 'Twice-marinated chops finished over open flame',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Fish Pakora',
                'desc' => 'Ajwain-spiced batter, lemon and green chutney',
                'tags' => [
                    'Fish',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Fish Pakora (Cod)',
                'desc' => 'Cod fillet in spiced gram-flour batter',
                'tags' => [
                    'Fish',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Roast Potato',
                'desc' => 'Whole spiced potatoes roasted until golden',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Fries',
                'desc' => 'Salted fries, served hot',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Veg Samosa',
                'desc' => 'Hand-folded pastry with spiced potato and pea',
                'tags' => [
                    'V',
                    'Nuts',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Veg Spring Roll',
                'desc' => 'Shredded vegetables in a crisp rolled pastry',
                'tags' => [
                    'V',
                    'Gluten',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Potato Wedges',
                'desc' => 'Seasoned wedges, crisp outside and soft through',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
        ],
    ],
    [
        'id' => 'mains',
        'n' => '03',
        'title' => 'Mains',
        'sub' => 'Slow-cooked and served hot',
        'blurb' => 'Onions browned properly, whole spices bloomed in ghee, then left alone until they are ready.',
        'image' => 'karahi-naan',
        'accent' => 'chicken-masala',
        'dishes' => [
            [
                'name' => 'Meat Masala',
                'desc' => 'Slow-cooked meat in a tomato, ginger and garlic masala',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Chicken Masala',
                'desc' => 'Chicken cooked down in masala and finished with coriander',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Meat Palak',
                'desc' => 'Meat cooked with spinach and whole spice',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chicken Palak',
                'desc' => 'Chicken with spinach, garlic and green chilli',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Kofta Aloo',
                'desc' => 'Minced meat koftas cooked with potato in gravy',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Butter Chicken',
                'desc' => 'Grilled chicken in a mild tomato and cream sauce',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Shahi Daal',
                'desc' => 'Lentils cooked long and tempered with ghee and cumin',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Chicken Jalfrezi',
                'desc' => 'Peppers and onion, cooked dry and hot',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Allo Palak',
                'desc' => 'Potato and spinach with turmeric and cumin',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Channa Curry',
                'desc' => 'Chickpeas in a rich tomato and amchur gravy',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Mix Veg',
                'desc' => 'Seasonal vegetables, light and dry-spiced',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
        ],
    ],
    [
        'id' => 'sides',
        'n' => '04',
        'title' => 'Rice & Breads',
        'sub' => 'The foundation',
        'blurb' => 'Aged basmati layered and steamed, and breads cooked to order and stacked under cloth.',
        'image' => 'biryani-claypot',
        'accent' => 'pulao-chicken',
        'dishes' => [
            [
                'name' => 'Meat Pilau Rice',
                'desc' => 'Basmati cooked in meat stock with whole spice',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Meat Biryani',
                'desc' => 'Layered, saffron-set, sealed and steamed',
                'tags' => [],
                'signature' => true,
            ],
            [
                'name' => 'Chicken Pilau Rice',
                'desc' => 'Chicken and rice cooked together in one pot',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chicken Biryani',
                'desc' => 'Layered chicken biryani with fried onion',
                'tags' => [],
                'signature' => false,
            ],
            [
                'name' => 'Chana Pilau',
                'desc' => 'Chickpea pilau with cumin and bay',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Boiled Rice',
                'desc' => 'Plain steamed basmati',
                'tags' => [
                    'V',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Roti',
                'desc' => 'Wholemeal, cooked on the tawa',
                'tags' => [
                    'V',
                    'Gluten',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Naan',
                'desc' => 'Soft leavened bread from the tandoor',
                'tags' => [
                    'V',
                    'Gluten',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Roghni Naan',
                'desc' => 'Enriched naan brushed with ghee and sesame',
                'tags' => [
                    'V',
                    'Gluten',
                ],
                'signature' => false,
            ],
        ],
    ],
    [
        'id' => 'desserts',
        'n' => '05',
        'title' => 'Desserts',
        'sub' => 'To finish',
        'blurb' => 'Warm, cold and syrup-soaked — the part of the table nobody leaves early.',
        'image' => 'halwa-silver',
        'accent' => 'gulab-jamun-spoon',
        'dishes' => [
            [
                'name' => 'Gajrella',
                'desc' => 'Slow-cooked carrot with milk, ghee and nuts',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => true,
            ],
            [
                'name' => 'Sweet Rice (Zarda)',
                'desc' => 'Sweet saffron rice with nuts and dried fruit',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => true,
            ],
            [
                'name' => 'Shahi Halwa',
                'desc' => 'Rich semolina halwa finished with pistachio',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Gulab Jamon',
                'desc' => 'Warm dumplings soaked in cardamom syrup',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Rasmalai',
                'desc' => 'Cheese discs in sweetened, thickened milk',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Kheer',
                'desc' => 'Rice pudding with cardamom and almond',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Ice Cream',
                'desc' => 'Served cold alongside the hot desserts',
                'tags' => [
                    'V',
                    'Milk',
                    'Nuts',
                ],
                'signature' => false,
            ],
        ],
    ],
    [
        'id' => 'drinks',
        'n' => '06',
        'title' => 'Hot Drinks',
        'sub' => 'Poured all evening',
        'blurb' => 'A tea station is the single best thing you can add to a late-running event.',
        'image' => 'desi-tea',
        'accent' => 'pink-tea',
        'dishes' => [
            [
                'name' => 'Desi Tea',
                'desc' => 'Boiled with cardamom and milk, served from an urn',
                'tags' => [
                    'V',
                    'Milk',
                ],
                'signature' => true,
            ],
            [
                'name' => 'Kashmiri Pink Tea',
                'desc' => 'Pink, salted and topped with pistachio',
                'tags' => [
                    'V',
                    'Milk',
                ],
                'signature' => false,
            ],
            [
                'name' => 'Mint Tea',
                'desc' => 'Fresh mint, light and clean',
                'tags' => [
                    'V',
                    'Milk',
                ],
                'signature' => false,
            ],
            [
                'name' => 'English Tea',
                'desc' => 'Standard brew with milk',
                'tags' => [
                    'V',
                    'Milk',
                ],
                'signature' => false,
            ],
        ],
    ],
];
