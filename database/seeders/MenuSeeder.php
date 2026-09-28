<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishIngredient;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

/**
 * Midland's actual menu, with recipes.
 *
 * Quantities are per 100 guests because that is how the kitchen estimates.
 * They are a sensible starting point taken from standard catering yields — the
 * chef should sit down once and correct them, which is the one thing that makes
 * the whole forecasting chain trustworthy.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * name_en, name_ur, course, £ per head, portion g, [flags], recipe
         * Recipe keys are inventory item names; values are per 100 guests.
         */
        $dishes = [
            [
                'Chicken Biryani', 'چکن بریانی', 'mains', 8.50, 350,
                ['is_signature' => true],
                ['Basmati Rice' => 14, 'Chicken (whole, halal)' => 18, 'Onions' => 8, 'Sunflower Oil' => 4,
                    'Garam Masala' => 0.6, 'Red Chilli Powder' => 0.4, 'Natural Yoghurt' => 5, 'Ginger' => 1, 'Garlic' => 1],
            ],
            [
                'Meat Masala', 'گوشت مسالہ', 'mains', 10.50, 300,
                ['is_signature' => true],
                ['Lamb Shoulder' => 20, 'Onions' => 10, 'Tomatoes' => 8, 'Sunflower Oil' => 5,
                    'Garam Masala' => 0.7, 'Turmeric' => 0.3, 'Ginger' => 1.2, 'Garlic' => 1.2],
            ],
            [
                'Karahi Chicken', 'کڑاہی چکن', 'mains', 9.00, 300,
                [],
                ['Chicken (whole, halal)' => 20, 'Tomatoes' => 10, 'Onions' => 6, 'Sunflower Oil' => 5,
                    'Ginger' => 1.5, 'Garlic' => 1.5, 'Red Chilli Powder' => 0.5],
            ],
            [
                'Chana Daal', 'چنے کی دال', 'mains', 4.50, 250,
                ['is_vegetarian' => true],
                ['Chana Dal' => 12, 'Onions' => 5, 'Tomatoes' => 4, 'Sunflower Oil' => 3, 'Turmeric' => 0.3],
            ],
            [
                'Meat Pilau Rice', 'پلاؤ', 'sides', 7.00, 300,
                [],
                ['Basmati Rice' => 14, 'Lamb Shoulder' => 10, 'Onions' => 6, 'Desi Ghee' => 3, 'Garam Masala' => 0.5],
            ],
            [
                'Plain Rice', 'سادہ چاول', 'sides', 2.50, 250,
                ['is_vegetarian' => true],
                ['Basmati Rice' => 12, 'Sunflower Oil' => 1],
            ],
            [
                'Naan', 'نان', 'sides', 1.20, 120,
                ['is_vegetarian' => true],
                ['Plain Flour' => 14, 'Full Fat Milk' => 3, 'Sunflower Oil' => 1],
            ],
            [
                'Samosa Chaat', 'سموسہ چاٹ', 'appetisers', 3.50, 180,
                ['is_vegetarian' => true, 'contains_dairy' => true, 'is_signature' => true],
                ['Chana Dal' => 5, 'Natural Yoghurt' => 8, 'Onions' => 3, 'Plain Flour' => 4, 'Sunflower Oil' => 3],
            ],
            [
                'Seekh Kebab', 'سیخ کباب', 'starters', 5.50, 150,
                [],
                ['Lamb Shoulder' => 12, 'Onions' => 3, 'Garam Masala' => 0.4, 'Ginger' => 0.8, 'Garlic' => 0.8],
            ],
            [
                'Chicken Tikka', 'چکن تکہ', 'starters', 5.00, 150,
                ['contains_dairy' => true],
                ['Chicken (whole, halal)' => 14, 'Natural Yoghurt' => 6, 'Red Chilli Powder' => 0.4, 'Garam Masala' => 0.3],
            ],
            [
                'Gajrella', 'گاجریلا', 'desserts', 3.00, 150,
                ['is_vegetarian' => true, 'contains_dairy' => true, 'contains_nuts' => true],
                ['Carrots' => 15, 'Full Fat Milk' => 12, 'Desi Ghee' => 2],
            ],
            [
                'Desi Tea', 'دیسی چائے', 'drinks', 1.00, 200,
                ['is_vegetarian' => true, 'contains_dairy' => true],
                ['Tea Leaves' => 0.8, 'Full Fat Milk' => 15],
            ],
        ];

        $items = InventoryItem::pluck('id', 'name_en');
        $missing = [];

        foreach ($dishes as $index => [$en, $ur, $course, $price, $grams, $flags, $recipe]) {
            $dish = Dish::updateOrCreate(
                ['name_en' => $en],
                array_merge([
                    'name_ur' => $ur,
                    'course' => $course,
                    'price_per_head' => (int) round($price * 100),
                    'portion_grams' => $grams,
                    'is_active' => true,
                    'sort_order' => $index,
                ], $flags)
            );

            foreach ($recipe as $itemName => $per100) {
                if (! isset($items[$itemName])) {
                    $missing[$itemName] = true;

                    continue;
                }

                $dish->ingredients()->updateOrCreate(
                    ['inventory_item_id' => $items[$itemName]],
                    ['quantity_per_100' => $per100],
                );
            }
        }

        $this->command?->info(sprintf(
            'Menu seeded: %d dishes, %d recipe lines.%s',
            Dish::count(),
            DishIngredient::count(),
            $missing === [] ? '' : ' Missing stock items: '.implode(', ', array_keys($missing)),
        ));
    }
}
