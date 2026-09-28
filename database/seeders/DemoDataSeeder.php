<?php

namespace Database\Seeders;

use App\Models\EquipmentItem;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data so the dashboard can be walked through before the client's own
 * spreadsheets are imported.
 *
 * Deliberately NOT called from DatabaseSeeder — a live install should start
 * empty rather than with invented stock levels somebody might trust. Run it
 * explicitly:
 *
 *     php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(StockLedger $ledger): void
    {
        $owner = User::whereHas('role', fn ($q) => $q->where('level', '>=', 80))->firstOrFail();

        $suppliers = collect([
            ['name' => 'Birmingham Halal Wholesale', 'contact_name' => 'Imran', 'phone' => '0121 555 0110', 'order_method' => 'whatsapp', 'lead_time_days' => 1],
            ['name' => 'Sabzi Mandi Produce', 'contact_name' => 'Hassan', 'phone' => '0121 555 0142', 'order_method' => 'phone', 'lead_time_days' => 1],
            ['name' => 'Punjab Cash & Carry', 'contact_name' => 'Yousaf', 'phone' => '0121 555 0187', 'order_method' => 'in_person', 'lead_time_days' => 0],
            ['name' => 'Midlands Catering Disposables', 'contact_name' => 'Sarah', 'phone' => '0121 555 0203', 'order_method' => 'online', 'lead_time_days' => 3],
        ])->map(fn (array $row) => Supplier::updateOrCreate(['name' => $row['name']], $row + ['is_active' => true]));

        /* name, category slug, unit, on hand, reorder at, usual order, cost, frequency, supplier index */
        $items = [
            ['Basmati Rice', 'باسمتی چاول', 'rice-grains', 'kg', 180, 60, 100, 1.85, 'weekly', 2],
            ['Chicken (whole, halal)', 'مرغی', 'meat-poultry', 'kg', 42, 60, 120, 3.40, 'daily', 0],
            ['Lamb Shoulder', 'دنبے کا گوشت', 'meat-poultry', 'kg', 55, 40, 80, 8.20, 'daily', 0],
            ['Onions', 'پیاز', 'vegetables', 'kg', 90, 50, 100, 0.75, 'weekly', 1],
            ['Tomatoes', 'ٹماٹر', 'vegetables', 'kg', 18, 25, 50, 1.10, 'daily', 1],
            ['Ginger', 'ادرک', 'vegetables', 'kg', 12, 8, 15, 3.10, 'weekly', 1],
            ['Garlic', 'لہسن', 'vegetables', 'kg', 14, 8, 15, 2.80, 'weekly', 1],
            ['Sunflower Oil', 'تیل', 'oil-ghee', 'litres', 65, 40, 80, 1.45, 'weekly', 2],
            ['Desi Ghee', 'دیسی گھی', 'oil-ghee', 'kg', 9, 12, 20, 6.90, 'weekly', 2],
            ['Chana Dal', 'چنے کی دال', 'lentils-pulses', 'kg', 34, 20, 40, 1.60, 'weekly', 2],
            ['Garam Masala', 'گرم مصالحہ', 'spices', 'kg', 6.5, 4, 10, 9.50, 'weekly', 2],
            ['Red Chilli Powder', 'لال مرچ', 'spices', 'kg', 11, 6, 12, 5.20, 'weekly', 2],
            ['Turmeric', 'ہلدی', 'spices', 'kg', 7, 5, 10, 4.10, 'weekly', 2],
            ['Full Fat Milk', 'دودھ', 'dairy', 'litres', 40, 30, 60, 0.95, 'daily', 1],
            ['Natural Yoghurt', 'دہی', 'dairy', 'kg', 22, 20, 40, 1.70, 'daily', 1],
            ['Plain Flour', 'میدہ', 'flour-bread', 'kg', 75, 40, 80, 0.80, 'weekly', 2],
            ['Tea Leaves', 'چائے کی پتی', 'tea-drinks', 'kg', 8, 5, 10, 7.40, 'weekly', 2],
            ['Carrots', 'گاجر', 'vegetables', 'kg', 30, 20, 40, 0.85, 'weekly', 1],
            ['Foil Trays (large)', 'فوائل ٹرے', 'trays-containers', 'boxes', 14, 20, 40, 18.50, 'per_event', 3],
            ['Foil Trays (small)', 'چھوٹی ٹرے', 'trays-containers', 'boxes', 26, 15, 30, 12.75, 'per_event', 3],
            ['Serviettes', 'نیپکن', 'disposables', 'boxes', 9, 10, 20, 9.20, 'per_event', 3],
            ['Cling Film', 'کلنگ فلم', 'bags-wrapping', 'rolls', 17, 10, 24, 3.60, 'weekly', 3],
        ];

        foreach ($items as [$en, $ur, $categorySlug, $unit, $onHand, $reorderAt, $orderSize, $cost, $frequency, $supplierIndex]) {
            $category = InventoryCategory::where('slug', $categorySlug)->first();

            if ($category === null) {
                continue;
            }

            $item = InventoryItem::updateOrCreate(
                ['name_en' => $en],
                [
                    'inventory_category_id' => $category->id,
                    'supplier_id' => $suppliers[$supplierIndex]->id,
                    'name_ur' => $ur,
                    'unit' => $unit,
                    'reorder_level' => $reorderAt,
                    'reorder_quantity' => $orderSize,
                    'unit_cost' => $cost,
                    'count_frequency' => $frequency,
                    'is_active' => true,
                ]
            );

            // Opening stock goes through the ledger so history starts honestly.
            if ($item->movements()->count() === 0) {
                $ledger->record($item, StockMovement::TYPE_OPENING, $onHand, $owner, null, $cost, __('Opening balance'));
            }
        }

        $equipment = [
            ['Chafing Dish (full size)', 'چافنگ ڈش', 'chafing', 60, 42.00],
            ['Chafing Burner Fuel', 'برنر فیول', 'chafing', 200, 1.20],
            ['Dinner Plates', 'پلیٹیں', 'crockery', 800, 1.80],
            ['Serving Spoons', 'سرونگ چمچ', 'cutlery', 150, 2.40],
            ['Tea Urn (20L)', 'چائے کا ارن', 'cooking', 6, 120.00],
            ['Serving Dish (large)', 'بڑی سرونگ ڈش', 'serving', 90, 14.50],
        ];

        foreach ($equipment as [$en, $ur, $category, $owned, $replacement]) {
            EquipmentItem::updateOrCreate(
                ['name_en' => $en],
                [
                    'name_ur' => $ur,
                    'category' => $category,
                    'quantity_owned' => $owned,
                    'replacement_cost' => (int) round($replacement * 100),
                    'is_active' => true,
                ]
            );
        }

        $staff = [
            ['Imran Hussain', 'kitchen', 'full_time', 13.50, 20.25],
            ['Bilal Ahmed', 'kitchen', 'full_time', 12.80, 19.20],
            ['Sana Riaz', 'service', 'part_time', 12.20, 18.30],
            ['Tariq Mahmood', 'delivery', 'full_time', 13.00, 19.50],
            ['Ayesha Khan', 'service', 'event_staff', 11.90, 17.85],
            ['Nadeem Iqbal', 'service', 'event_staff', 11.90, 17.85],
            ['Rukhsana Bibi', 'kitchen', 'part_time', 12.40, 18.60],
            ['Zain Ali', 'delivery', 'event_staff', 12.00, 18.00],
        ];

        foreach ($staff as [$name, $department, $type, $rate, $overtime]) {
            StaffProfile::updateOrCreate(
                ['full_name' => $name],
                [
                    'department' => $department,
                    'employment_type' => $type,
                    'hourly_rate' => $rate,
                    'overtime_rate' => $overtime,
                    'holiday_allowance_hours' => $type === 'full_time' ? 224 : 96,
                    'is_active' => true,
                    'started_on' => now()->subMonths(random_int(3, 40)),
                ]
            );
        }

        $orders = [
            ['Nadia Begum', 'Wedding', 8, 320, 'Grand Station Banqueting', 'confirmed', 4850.00, 1000.00],
            ['Mohammed Aslam', 'Khatam Shareef', 3, 80, 'Home — Small Heath', 'confirmed', 720.00, 0],
            ['Sparkhill Community Trust', 'Corporate Events', 16, 150, 'Sparkhill Hall', 'draft', 1875.00, 0],
            ['Fatima Malik', 'Parties & Birthdays', 22, 45, 'Home — Alum Rock', 'confirmed', 540.00, 200.00],
        ];

        foreach ($orders as [$customer, $type, $daysAhead, $guests, $venue, $status, $total, $deposit]) {
            Order::updateOrCreate(
                ['customer_name' => $customer, 'event_date' => now()->addDays($daysAhead)->toDateString()],
                [
                    'order_type' => $type,
                    'guests' => $guests,
                    'venue' => $venue,
                    'status' => $status,
                    'service_style' => 'delivered_and_served',
                    'total_amount' => (int) round($total * 100),
                    'deposit_due' => (int) round($deposit * 100),
                    'staff_required' => (int) ceil($guests / 40),
                    'source' => 'phone',
                    'taken_by' => $owner->id,
                ]
            );
        }

        // A kitchen-manager account so the access limits can be seen working:
        // counting and waste yes, pricing, purchasing and pay no.
        $kitchenRole = Role::where('slug', Role::MANAGER)->first();

        if ($kitchenRole !== null) {
            $kitchenUser = User::updateOrCreate(
                ['email' => 'kitchen@midlandcateringltd.co.uk'],
                [
                    'name' => 'Kitchen Manager',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role_id' => $kitchenRole->id,
                    'locale' => 'ur',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            StaffProfile::updateOrCreate(
                ['user_id' => $kitchenUser->id],
                [
                    'full_name' => 'Kitchen Manager',
                    'employment_type' => 'full_time',
                    'department' => 'kitchen',
                    'hourly_rate' => 14.00,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Demo data seeded: '.InventoryItem::count().' items, '.StaffProfile::count().' staff, '.Order::count().' orders.');
    }
}
