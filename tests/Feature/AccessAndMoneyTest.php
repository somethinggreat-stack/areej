<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\OperationsFeed;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prices reach only the people meant to see them, on the server as well as
 * in the page, and quotes behave the way the owner's settings say.
 */
class AccessAndMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->value('id'),
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
            'total_amount' => 480000,
            'deposit_due' => 120000,
        ], $overrides));
    }

    /* ---------------------------------------------------------- quotes */

    public function test_quotes_are_open_to_sales_and_finance_but_not_the_kitchen_or_purchasing(): void
    {
        $this->actingAs($this->userWithRole(Role::SALES))->get('/dashboard/quotes')->assertOk();
        $this->actingAs($this->userWithRole(Role::FINANCE))->get('/dashboard/quotes')->assertOk();

        $this->actingAs($this->userWithRole(Role::MANAGER))->get('/dashboard/quotes')->assertForbidden();
        $this->actingAs($this->userWithRole(Role::PURCHASING))->get('/dashboard/quotes')->assertForbidden();
    }

    /* ------------------------------------------------------ money on a job */

    public function test_the_invoice_and_payment_addresses_are_blocked_below_management(): void
    {
        $order = $this->order();
        $sales = $this->userWithRole(Role::SALES);

        $this->actingAs($sales)->get("/dashboard/orders/{$order->id}/invoice")->assertForbidden();
        $this->actingAs($sales)->post("/dashboard/orders/{$order->id}/payments", [
            'amount' => 100, 'kind' => 'deposit', 'method' => 'cash', 'paid_on' => today()->toDateString(),
        ])->assertForbidden();
        $this->actingAs($sales)->post("/dashboard/orders/{$order->id}/items", [
            'description' => 'Extra', 'quantity' => 1, 'unit_price' => 50,
        ])->assertForbidden();

        $this->actingAs($this->userWithRole(Role::MANAGEMENT))->get("/dashboard/orders/{$order->id}/invoice")->assertOk();
    }

    public function test_saving_a_job_below_management_keeps_its_price(): void
    {
        $order = $this->order();

        $this->actingAs($this->userWithRole(Role::SALES))->patch("/dashboard/orders/{$order->id}", [
            'customer_name' => 'Nadia Begum',
            'event_date' => $order->event_date->toDateString(),
            'service_style' => 'delivered_and_served',
            'status' => 'confirmed',
            'venue' => 'Grand Station',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Grand Station', $order->venue);
        $this->assertSame(480000, $order->total_amount);
        $this->assertSame(120000, $order->deposit_due);
    }

    public function test_the_unpaid_money_alert_is_only_for_management(): void
    {
        $this->order(['event_date' => today()->subDays(3)]);
        $feed = app(OperationsFeed::class);

        $titles = fn (User $user) => $feed->alerts($user)->pluck('title')->all();

        $this->assertContains('Unpaid after the event', $titles($this->userWithRole(Role::MANAGEMENT)));
        $this->assertNotContains('Unpaid after the event', $titles($this->userWithRole(Role::MANAGER)));
        $this->assertNotContains('Unpaid after the event', $titles($this->userWithRole(Role::STAFF)));
    }

    /* ------------------------------------------------------- quote rules */

    public function test_an_accepted_quote_keeps_its_discount_when_the_job_changes(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 100, 'status' => 'draft', 'discount_percent' => 10]);

        $this->actingAs($manager)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Buffet', 'quantity' => 1, 'unit_price' => 1000,
        ]);
        $this->actingAs($manager)->post("/dashboard/quotes/{$quote->id}/accept")->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(90000, $order->total_amount);

        $this->actingAs($manager)->post("/dashboard/orders/{$order->id}/items", [
            'description' => 'Hot drinks station', 'quantity' => 1, 'unit_price' => 50,
        ]);

        $this->assertSame(95000, $order->fresh()->total_amount, 'The 10% discount survives a later line.');
    }

    public function test_a_sent_quote_past_its_date_expires_on_its_own(): void
    {
        $stale = Quote::create(['customer_name' => 'Old', 'guests' => 50, 'status' => 'sent', 'sent_at' => now()->subMonth(), 'valid_until' => today()->subDay()]);
        $current = Quote::create(['customer_name' => 'New', 'guests' => 50, 'status' => 'sent', 'sent_at' => now(), 'valid_until' => today()->addWeek()]);

        $this->actingAs($this->userWithRole(Role::SALES))->get('/dashboard/quotes')->assertOk();

        $this->assertSame('expired', $stale->fresh()->status);
        $this->assertSame('sent', $current->fresh()->status);
    }

    public function test_the_chase_after_setting_decides_when_a_quote_is_flagged(): void
    {
        Setting::put('chase_quote_after_days', 7, 'int', 'sales');
        Quote::create(['customer_name' => 'Quiet', 'guests' => 50, 'status' => 'sent', 'sent_at' => now()->subDays(5), 'valid_until' => today()->addMonth()]);

        $titles = app(OperationsFeed::class)->alerts($this->userWithRole(Role::SALES))->pluck('title');
        $this->assertNotContains('Quotes with no reply', $titles, 'Five days is inside a seven-day window.');

        Setting::put('chase_quote_after_days', 3, 'int', 'sales');
        $titles = app(OperationsFeed::class)->alerts($this->userWithRole(Role::SALES))->pluck('title');
        $this->assertContains('Quotes with no reply', $titles);
    }

    public function test_vat_is_shown_inside_the_total_once_registered(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 100, 'status' => 'draft']);
        $this->actingAs($manager)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Buffet', 'quantity' => 1, 'unit_price' => 1200,
        ]);

        $this->actingAs($manager)->get("/dashboard/quotes/{$quote->id}/print")->assertDontSee('Of which VAT');

        Setting::put('vat_registered', true, 'bool', 'finance');

        $this->actingAs($manager)->get("/dashboard/quotes/{$quote->id}/print")
            ->assertSee('Of which VAT at 20%')
            ->assertSee('£200.00');
    }

    /* -------------------------------------------------- staff and the overview */

    public function test_staff_see_their_own_timesheet(): void
    {
        $cook = $this->userWithRole(Role::STAFF);
        $profile = StaffProfile::create(['user_id' => $cook->id, 'full_name' => 'Imran Hussain', 'employment_type' => 'full_time', 'department' => 'kitchen', 'hourly_rate' => 12, 'is_active' => true]);
        AttendanceRecord::create([
            'staff_profile_id' => $profile->id,
            'worked_on' => today(),
            'clock_in_at' => today()->setTime(9, 0),
            'clock_out_at' => today()->setTime(17, 0),
            'status' => 'closed',
        ]);

        $this->actingAs($cook)->get('/dashboard/my-timesheet')
            ->assertOk()
            ->assertSee('8.00')
            ->assertDontSee('£');
    }

    public function test_a_login_without_a_rota_record_is_told_how_to_get_one(): void
    {
        $this->actingAs($this->userWithRole(Role::STAFF))->get('/dashboard/my-timesheet')
            ->assertOk()
            ->assertSee('not linked to the rota');
    }

    public function test_the_overview_offers_staff_no_links_they_cannot_open(): void
    {
        $this->order(['event_date' => today()]);

        $this->actingAs($this->userWithRole(Role::STAFF))->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('calendar'), false)
            ->assertDontSee(route('quotes'), false)
            ->assertDontSee(route('attendance'), false)
            ->assertDontSee(route('prep'), false);
    }
}
