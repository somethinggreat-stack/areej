<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            InventoryStarterSeeder::class,
        ]);

        $this->seedTeam();
    }

    /**
     * The real team, from Midland Catering's own menu board. Passwords are
     * placeholders to be changed on first login.
     */
    private function seedTeam(): void
    {
        $team = [
            ['Raja Mahmood Khan', 'raja@midlandcateringltd.co.uk', '07976 289 686', Role::OWNER, 'management'],
            ['Areej', 'areej@midlandcateringltd.co.uk', null, Role::MANAGEMENT, 'management'],
            ['Kabir Kayani', 'kabir@midlandcateringltd.co.uk', '07568 368 686', Role::MANAGEMENT, 'management'],
            ['Azram Khan', 'azram@midlandcateringltd.co.uk', '07929 885 106', Role::MANAGEMENT, 'management'],
        ];

        foreach ($team as [$name, $email, $phone, $roleSlug, $department]) {
            $role = Role::where('slug', $roleSlug)->firstOrFail();

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('ChangeMe!2026'),
                    'role_id' => $role->id,
                    'phone' => $phone,
                    'locale' => 'en',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            StaffProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $name,
                    'phone' => $phone,
                    'employment_type' => 'full_time',
                    'department' => $department,
                    'is_active' => true,
                ]
            );
        }
    }
}
