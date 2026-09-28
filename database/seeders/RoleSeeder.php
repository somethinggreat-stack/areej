<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Access is a ladder, not a matrix.
 *
 * The client described it that way — "Areej and management see everything, the
 * kitchen manager counts stock but never sees pay" — so a level comparison
 * expresses it directly and cannot drift out of step. The specialist roles sit
 * between kitchen and management so a purchasing clerk can raise orders without
 * also seeing wages.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => Role::OWNER,
                'name_en' => 'Owner',
                'name_ur' => 'مالک',
                'level' => 100,
                'abilities' => ['*'],
            ],
            [
                'slug' => Role::MANAGEMENT,
                'name_en' => 'Management',
                'name_ur' => 'انتظامیہ',
                'level' => 80,
                'abilities' => [
                    'inventory.*', 'equipment.*', 'orders.*', 'quotes.*',
                    'attendance.*', 'staff.*', 'enquiries.*', 'reports.*',
                    'purchasing.*', 'finance.*',
                ],
            ],
            [
                'slug' => 'finance',
                'name_en' => 'Finance',
                'name_ur' => 'اکاؤنٹس',
                'level' => 60,
                'abilities' => [
                    'finance.*', 'reports.*', 'orders.view', 'quotes.view',
                    'purchasing.view', 'expenses.*',
                ],
            ],
            [
                'slug' => Role::MANAGER,
                'name_en' => 'Kitchen Manager',
                'name_ur' => 'کچن منیجر',
                'level' => 50,
                'abilities' => [
                    'inventory.view', 'inventory.count', 'inventory.waste',
                    'equipment.checkout', 'equipment.checkin',
                    'dishes.*', 'prep.*', 'orders.view',
                ],
            ],
            [
                'slug' => 'purchasing',
                'name_en' => 'Purchasing',
                'name_ur' => 'خریداری',
                'level' => 40,
                'abilities' => [
                    'purchasing.*', 'suppliers.*', 'inventory.view', 'inventory.count',
                ],
            ],
            [
                'slug' => 'sales',
                'name_en' => 'Sales & Bookings',
                'name_ur' => 'بکنگ',
                'level' => 30,
                'abilities' => [
                    'enquiries.*', 'quotes.*', 'orders.*',
                ],
            ],
            [
                'slug' => Role::STAFF,
                'name_en' => 'Staff',
                'name_ur' => 'عملہ',
                'level' => 10,
                'abilities' => ['attendance.self', 'prep.view'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
