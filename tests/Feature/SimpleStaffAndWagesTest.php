<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WagePayment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The simplified people side: add someone with just a name, type a day's
 * hours in one grid, record cash wages with one click.
 */
class SimpleStaffAndWagesTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->manager = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);
    }

    private function person(string $name = 'Imran Hussain'): StaffProfile
    {
        return StaffProfile::create([
            'full_name' => $name,
            'employment_type' => 'full_time',
            'department' => 'kitchen',
            'hourly_rate' => 12,
            'is_active' => true,
        ]);
    }

    public function test_someone_can_be_added_with_only_a_name(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/staff', ['full_name' => 'Sana Riaz'])
            ->assertSessionHasNoErrors();

        $sana = StaffProfile::where('full_name', 'Sana Riaz')->firstOrFail();
        $this->assertSame('full_time', $sana->employment_type);
        $this->assertSame('kitchen', $sana->department);
    }

    public function test_a_days_hours_are_saved_in_one_go_and_blank_rows_are_skipped(): void
    {
        $imran = $this->person();
        $bilal = $this->person('Bilal Ahmed');
        $sana = $this->person('Sana Riaz');

        $this->actingAs($this->manager)->post('/dashboard/attendance/quick', [
            'worked_on' => today()->subDay()->toDateString(),
            'shifts' => [
                $imran->id => ['start' => '09:00', 'end' => '17:30', 'break' => 30],
                $bilal->id => ['start' => '18:00', 'end' => '01:00', 'break' => ''],
                $sana->id => ['start' => '', 'end' => '', 'break' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, AttendanceRecord::count());
        $this->assertSame(8.0, AttendanceRecord::where('staff_profile_id', $imran->id)->first()->paidHours());
        $this->assertSame(7.0, AttendanceRecord::where('staff_profile_id', $bilal->id)->first()->paidHours(), 'A finish after midnight is the next morning.');
        $this->assertSame('closed', AttendanceRecord::first()->status);
    }

    public function test_an_empty_grid_is_refused(): void
    {
        $imran = $this->person();

        $this->actingAs($this->manager)->post('/dashboard/attendance/quick', [
            'worked_on' => today()->toDateString(),
            'shifts' => [$imran->id => ['start' => '', 'end' => '']],
        ])->assertSessionHasErrors('form');
    }

    public function test_cash_wages_are_recorded_and_shown_on_the_persons_page(): void
    {
        $imran = $this->person();
        $monday = now()->startOfWeek()->toDateString();

        $this->actingAs($this->manager)->post('/dashboard/timesheets/pay', [
            'staff_profile_id' => $imran->id,
            'week' => $monday,
            'amount' => '96.00',
        ])->assertSessionHasNoErrors();

        $payment = WagePayment::firstOrFail();
        $this->assertSame(9600, $payment->amount);
        $this->assertSame('cash', $payment->method);
        $this->assertSame($monday, $payment->week_start->toDateString());

        $this->actingAs($this->manager)->get("/dashboard/staff/{$imran->id}")
            ->assertOk()
            ->assertSee('Wages paid')
            ->assertSee('£96.00');

        $this->actingAs($this->manager)->delete("/dashboard/wage-payments/{$payment->id}")->assertRedirect();
        $this->assertDatabaseCount('wage_payments', 0);
    }

    public function test_wages_are_for_management_only(): void
    {
        $kitchen = User::factory()->create(['role_id' => Role::where('slug', Role::MANAGER)->value('id'), 'is_active' => true]);

        $this->actingAs($kitchen)->post('/dashboard/timesheets/pay', [
            'staff_profile_id' => $this->person()->id,
            'week' => today()->toDateString(),
            'amount' => 50,
        ])->assertForbidden();
    }

    public function test_the_simple_menu_hides_the_extra_areas_until_switched_off(): void
    {
        $this->actingAs($this->manager)->get('/dashboard')
            ->assertDontSee(route('purchase-orders'), false)
            ->assertDontSee(route('equipment'), false);

        Setting::put('simple_menu', false, 'bool', 'menu');

        $this->actingAs($this->manager)->get('/dashboard')
            ->assertSee(route('equipment'), false);
    }
}
