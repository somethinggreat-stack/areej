<?php

namespace Database\Factories;

use App\Models\StaffProfile;
use App\Models\WagePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WagePayment>
 */
class WagePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $week = now()->startOfWeek();

        return [
            'staff_profile_id' => fn () => StaffProfile::create([
                'full_name' => fake()->name(),
                'employment_type' => 'full_time',
                'department' => 'kitchen',
                'hourly_rate' => 12,
                'is_active' => true,
            ])->id,
            'week_start' => $week->toDateString(),
            'amount' => fake()->numberBetween(20000, 60000),
            'paid_on' => $week->copy()->addDays(6)->toDateString(),
            'method' => 'cash',
        ];
    }
}
