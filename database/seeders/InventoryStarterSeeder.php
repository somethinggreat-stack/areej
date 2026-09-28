<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;

/**
 * Starting categories drawn from Midland Catering's actual menu board and the
 * "track everything" answer: food, packaging and equipment.
 *
 * Items themselves are not seeded — those come from the client's own stock
 * spreadsheets at setup so nothing is invented or retyped.
 */
class InventoryStarterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Meat & Poultry', 'گوشت اور مرغی', 'meat-poultry', 'food'],
            ['Fish', 'مچھلی', 'fish', 'food'],
            ['Rice & Grains', 'چاول اور اناج', 'rice-grains', 'food'],
            ['Flour & Bread', 'آٹا اور روٹی', 'flour-bread', 'food'],
            ['Vegetables', 'سبزیاں', 'vegetables', 'food'],
            ['Lentils & Pulses', 'دالیں', 'lentils-pulses', 'food'],
            ['Spices', 'مصالحہ جات', 'spices', 'food'],
            ['Oil & Ghee', 'تیل اور گھی', 'oil-ghee', 'food'],
            ['Dairy', 'ڈیری', 'dairy', 'food'],
            ['Dessert Ingredients', 'میٹھے کا سامان', 'dessert-ingredients', 'food'],
            ['Tea & Drinks', 'چائے اور مشروبات', 'tea-drinks', 'food'],
            ['Foil Trays & Containers', 'فوائل ٹرے اور ڈبے', 'trays-containers', 'packaging'],
            ['Bags & Wrapping', 'تھیلے اور ریپنگ', 'bags-wrapping', 'packaging'],
            ['Disposables', 'ڈسپوزایبل', 'disposables', 'consumable'],
            ['Cleaning', 'صفائی', 'cleaning', 'consumable'],
            ['Chafing Dishes & Burners', 'چافنگ ڈش اور برنر', 'chafing-burners', 'equipment'],
            ['Crockery', 'برتن', 'crockery', 'equipment'],
            ['Cutlery', 'چھری کانٹے', 'cutlery', 'equipment'],
            ['Serving Dishes', 'سرونگ ڈشیں', 'serving-dishes', 'equipment'],
            ['Cooking Equipment', 'کھانا پکانے کا سامان', 'cooking-equipment', 'equipment'],
        ];

        foreach ($categories as $index => [$en, $ur, $slug, $kind]) {
            InventoryCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_en' => $en,
                    'name_ur' => $ur,
                    'kind' => $kind,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
