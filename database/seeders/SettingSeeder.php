<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Business rules the owner can change without a developer. Defaults reflect how
 * Midland already work rather than generic placeholders.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Sales
            ['quote_validity_days', 14, 'int', 'sales'],
            ['deposit_percent', 25, 'int', 'sales'],
            ['minimum_guests', 20, 'int', 'sales'],
            ['chase_quote_after_days', 3, 'int', 'sales'],

            // Kitchen and stock
            ['low_stock_lead_days', 3, 'int', 'stock'],
            ['count_day', 'monday', 'string', 'stock'],
            ['waste_alert_percent', 5, 'int', 'stock'],

            // People
            ['overtime_after_hours', 40, 'int', 'people'],
            ['late_grace_minutes', 5, 'int', 'people'],
            ['staff_per_guests', 40, 'int', 'people'],

            // Money
            ['vat_registered', 0, 'bool', 'money'],
            ['vat_percent', 20, 'int', 'money'],
            ['currency_symbol', '£', 'string', 'money'],
        ];

        foreach ($defaults as [$key, $value, $type, $group]) {
            // updateOrCreate on key only, so re-seeding never overwrites a
            // figure the owner has since changed.
            if (Setting::where('key', $key)->exists()) {
                continue;
            }

            Setting::create([
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'type' => $type,
                'group' => $group,
            ]);
        }
    }
}
