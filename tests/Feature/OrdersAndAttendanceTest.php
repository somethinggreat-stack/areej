<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Timesheet;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersAndAttendanceTest extends TestCase
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

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Nadia Begum',
            'event_date' => today()->addWeek(),
            'guests' => 300,
            'service_style' => 'delivered_and_served',
            'status' => 'confirmed',
            'total_amount' => 480000,   // £4,800.00
        ], $overrides));
    }

    /* ------------------------------------------------------------- orders */

    public function test_an_order_can_be_taken_by_hand(): void
    {
        $this->actingAs($this->manager)
            ->post('/dashboard/orders', [
                'customer_name' => 'Mohammed Aslam',
                'phone' => '07700 900123',
                'event_date' => today()->addDays(10)->toDateString(),
                'guests' => 80,
                'service_style' => 'delivery',
                'status' => 'confirmed',
                'total_amount' => 720.50,
            ])
            ->assertRedirect();

        $order = Order::where('customer_name', 'Mohammed Aslam')->firstOrFail();

        $this->assertSame(72050, $order->total_amount, 'Pounds are stored as pence.');
        $this->assertStringStartsWith('ORD-', $order->reference);
    }

    public function test_money_is_held_in_pence_so_totals_never_drift(): void
    {
        $order = $this->order(['total_amount' => 0]);

        // Three lines that would each lose a fraction of a penny as floats.
        foreach ([['a', 3, 0.10], ['b', 3, 0.10], ['c', 3, 0.10]] as [$name, $quantity, $price]) {
            $this->actingAs($this->manager)->post("/dashboard/orders/{$order->id}/items", [
                'description' => $name,
                'quantity' => $quantity,
                'unit_price' => $price,
            ]);
        }

        $this->assertSame(90, $order->fresh()->total_amount);
        $this->assertSame(0.90, round($order->fresh()->totalInPounds(), 2));
    }

    public function test_part_payments_add_up_and_the_balance_follows(): void
    {
        $order = $this->order();

        $this->assertSame('unpaid', $order->paymentStatus());

        $this->actingAs($this->manager)->post("/dashboard/orders/{$order->id}/payments", [
            'amount' => 1000,
            'method' => 'bank_transfer',
            'kind' => 'deposit',
            'paid_on' => today()->toDateString(),
        ]);

        $order->refresh()->load('payments');
        $this->assertSame(100000, $order->paidAmount());
        $this->assertSame(380000, $order->balanceAmount());
        $this->assertSame('part_paid', $order->paymentStatus());

        $this->actingAs($this->manager)->post("/dashboard/orders/{$order->id}/payments", [
            'amount' => 3800,
            'method' => 'cash',
            'kind' => 'balance',
            'paid_on' => today()->toDateString(),
        ]);

        $order->refresh()->load('payments');
        $this->assertSame(0, $order->balanceAmount());
        $this->assertTrue($order->isPaid());
    }

    public function test_a_refund_comes_back_off_the_paid_total(): void
    {
        $order = $this->order();

        foreach ([['deposit', 1000], ['refund', 400]] as [$kind, $amount]) {
            $this->actingAs($this->manager)->post("/dashboard/orders/{$order->id}/payments", [
                'amount' => $amount,
                'method' => 'cash',
                'kind' => $kind,
                'paid_on' => today()->toDateString(),
            ]);
        }

        $this->assertSame(60000, $order->refresh()->load('payments')->paidAmount());
    }

    public function test_an_enquiry_converts_without_retyping_anything(): void
    {
        $enquiry = Enquiry::create([
            'name' => 'Fatima Malik',
            'phone' => '07700 900456',
            'event_type' => 'Parties & Birthdays',
            'event_date' => today()->addDays(30),
            'guests' => 45,
            'venue' => 'Home — Alum Rock',
            'service_style' => 'Full buffet setup',
            'status' => 'new',
        ]);

        $this->actingAs($this->manager)
            ->post("/dashboard/enquiries/{$enquiry->id}/convert")
            ->assertRedirect();

        $order = $enquiry->fresh()->order;

        $this->assertNotNull($order);
        $this->assertSame('Fatima Malik', $order->customer_name);
        $this->assertSame(45, $order->guests);
        $this->assertSame('full_buffet', $order->service_style, 'The website wording is mapped to the orders vocabulary.');
        $this->assertSame('won', $enquiry->fresh()->status);
    }

    public function test_converting_the_same_enquiry_twice_does_not_duplicate_the_order(): void
    {
        $enquiry = Enquiry::create(['name' => 'Repeat Test', 'status' => 'new']);

        $this->actingAs($this->manager)->post("/dashboard/enquiries/{$enquiry->id}/convert");
        $this->actingAs($this->manager)->post("/dashboard/enquiries/{$enquiry->id}/convert");

        $this->assertSame(1, Order::where('enquiry_id', $enquiry->id)->count());
    }

    /* --------------------------------------------------------- attendance */

    private function staff(array $overrides = []): StaffProfile
    {
        return StaffProfile::create(array_merge([
            'full_name' => 'Imran Hussain',
            'employment_type' => 'full_time',
            'department' => 'kitchen',
            'hourly_rate' => 12.00,
            'overtime_rate' => 18.00,
            'is_active' => true,
        ], $overrides));
    }

    public function test_staff_are_rostered_onto_a_job_before_they_clock_in(): void
    {
        $staff = $this->staff();
        $order = $this->order();

        $this->actingAs($this->manager)->post('/dashboard/attendance/roster', [
            'order_id' => $order->id,
            'worked_on' => today()->toDateString(),
            'scheduled_start' => '09:00',
            'scheduled_end' => '17:00',
            'staff_profile_ids' => [$staff->id],
        ]);

        $record = AttendanceRecord::firstOrFail();

        $this->assertSame('scheduled', $record->status);
        $this->assertNull($record->clock_in_at);
        $this->assertSame($order->id, $record->order_id);
        $this->assertSame('12.00', $record->hourly_rate, 'The rate is captured when the shift is created.');
    }

    public function test_rostering_the_same_person_twice_does_not_duplicate_the_shift(): void
    {
        $staff = $this->staff();

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->manager)->post('/dashboard/attendance/roster', [
                'worked_on' => today()->toDateString(),
                'scheduled_start' => '09:00',
                'staff_profile_ids' => [$staff->id],
            ]);
        }

        $this->assertSame(1, AttendanceRecord::count());
    }

    public function test_lateness_is_measured_against_the_rostered_start(): void
    {
        $record = AttendanceRecord::create([
            'staff_profile_id' => $this->staff()->id,
            'worked_on' => today(),
            'scheduled_start_at' => today()->setTime(9, 0),
            'clock_in_at' => today()->setTime(9, 25),
            'status' => 'open',
        ]);

        $this->assertSame(25, $record->minutesLate());
        $this->assertTrue($record->isLate());

        $onTime = AttendanceRecord::create([
            'staff_profile_id' => $this->staff(['full_name' => 'Bilal Ahmed'])->id,
            'worked_on' => today(),
            'scheduled_start_at' => today()->setTime(9, 0),
            'clock_in_at' => today()->setTime(8, 55),
            'status' => 'open',
        ]);

        $this->assertSame(0, $onTime->minutesLate());
        $this->assertFalse($onTime->isLate());
    }

    public function test_paid_hours_exclude_the_break(): void
    {
        $record = AttendanceRecord::create([
            'staff_profile_id' => $this->staff()->id,
            'worked_on' => today(),
            'clock_in_at' => today()->setTime(9, 0),
            'clock_out_at' => today()->setTime(17, 30),
            'break_minutes' => 30,
            'status' => 'closed',
        ]);

        $this->assertSame(8.0, $record->paidHours());
    }

    public function test_overtime_applies_per_week_not_per_shift(): void
    {
        $staff = $this->staff();
        $monday = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);

        // Five ten-hour days: 50 hours, so 10 of them are overtime.
        foreach (range(0, 4) as $offset) {
            $day = $monday->addDays($offset);

            AttendanceRecord::create([
                'staff_profile_id' => $staff->id,
                'worked_on' => $day,
                'clock_in_at' => $day->setTime(8, 0),
                'clock_out_at' => $day->setTime(18, 0),
                'status' => 'closed',
                'hourly_rate' => 12.00,
                'overtime_rate' => 18.00,
            ]);
        }

        $week = app(Timesheet::class)->forStaff($staff, $monday);

        $this->assertSame(50.0, $week['total_hours']);
        $this->assertSame(40.0, $week['normal_hours']);
        $this->assertSame(10.0, $week['overtime_hours']);
        $this->assertSame(480.0, $week['normal_pay']);      // 40 × 12
        $this->assertSame(180.0, $week['overtime_pay']);    // 10 × 18
        $this->assertSame(660.0, $week['total_pay']);
    }

    public function test_only_closed_shifts_are_paid(): void
    {
        $staff = $this->staff();
        $monday = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);

        AttendanceRecord::create([
            'staff_profile_id' => $staff->id,
            'worked_on' => $monday,
            'clock_in_at' => $monday->setTime(9, 0),
            'status' => 'open',    // still on site
            'hourly_rate' => 12.00,
        ]);

        $this->assertSame(0.0, app(Timesheet::class)->forStaff($staff, $monday)['total_hours']);
    }

    public function test_approving_a_week_freezes_the_rates(): void
    {
        $staff = $this->staff();
        $monday = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);

        $record = AttendanceRecord::create([
            'staff_profile_id' => $staff->id,
            'worked_on' => $monday,
            'clock_in_at' => $monday->setTime(9, 0),
            'clock_out_at' => $monday->setTime(17, 0),
            'status' => 'closed',
        ]);

        $this->actingAs($this->manager)->post('/dashboard/timesheets/approve', [
            'week' => $monday->toDateString(),
        ]);

        $record->refresh();
        $this->assertSame('approved', $record->status);
        $this->assertSame('12.00', $record->hourly_rate);

        // A later pay rise must not rewrite the approved week.
        $staff->update(['hourly_rate' => 15.00]);
        $this->assertSame('12.00', $record->fresh()->hourly_rate);
    }

    public function test_attendance_and_pay_are_management_only(): void
    {
        $kitchen = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGER)->value('id'),
            'is_active' => true,
        ]);

        $this->actingAs($kitchen)->get('/dashboard/attendance')->assertForbidden();
        $this->actingAs($kitchen)->get('/dashboard/timesheets')->assertForbidden();
    }
}
