<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The Monday summary adds up one week of orders and says who still owes.
 */
class WeeklySummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->manager = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);

        $this->monday = now()->subWeeks(2)->startOfWeek();
    }

    private function order(string $name, string $phone, int $total, int $paid, int $dayOfWeek = 0, string $status = 'completed'): Order
    {
        $order = Order::create([
            'customer_name' => $name,
            'phone' => $phone,
            'event_date' => $this->monday->copy()->addDays($dayOfWeek),
            'service_style' => 'not_set',
            'status' => $status,
            'total_amount' => $total,
        ]);

        if ($paid > 0) {
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $paid,
                'method' => 'cash',
                'kind' => 'part_payment',
                'paid_on' => $this->monday->copy()->addDays($dayOfWeek),
            ]);
        }

        return $order;
    }

    public function test_the_week_is_added_up_and_split_into_paid_and_owing(): void
    {
        $this->order('Shahzad', '07566 633366', 30000, 30000, 1);
        $this->order('Latif', '07700 111222', 25000, 10000, 3);
        $this->order('Latif', '07700111222', 5000, 0, 5);
        $this->order('Cancelled Customer', '07700 999999', 99900, 0, 2, 'cancelled');
        $this->order('Next Week', '07700 888888', 12300, 0, 8);

        $this->actingAs($this->manager)->get('/dashboard/weekly-summary?week='.$this->monday->toDateString())
            ->assertOk()
            ->assertViewHas('orderCount', 3)
            ->assertViewHas('customerCount', 2)
            ->assertViewHas('sales', 60000)
            ->assertViewHas('paidOnOrders', 40000)
            ->assertViewHas('owed', 20000)
            ->assertViewHas('receivedThisWeek', 40000)
            ->assertViewHas('paidInFull', fn ($c) => $c->pluck('name')->all() === ['Shahzad'])
            ->assertViewHas('stillOwing', fn ($c) => $c->first()['name'] === 'Latif' && $c->first()['owed'] === 20000)
            ->assertSee('£200.00', false)
            ->assertDontSee('Cancelled Customer')
            ->assertDontSee('Next Week');
    }

    public function test_old_unpaid_orders_show_as_overdue(): void
    {
        $this->order('Latif', '07700 111222', 25000, 10000, 1);

        $this->actingAs($this->manager)->get('/dashboard/weekly-summary?week='.$this->monday->toDateString())
            ->assertViewHas('overdue', fn ($c) => $c->count() === 1)
            ->assertViewHas('overdueTotal', 15000);
    }

    public function test_it_opens_on_last_week(): void
    {
        $this->actingAs($this->manager)->get('/dashboard/weekly-summary')
            ->assertViewHas('start', fn ($start) => $start->isSameDay(now()->subWeek()->startOfWeek()));
    }

    public function test_the_summary_exports_to_excel(): void
    {
        $this->order('Shahzad', '07566 633366', 30000, 30000, 1);

        $csv = $this->actingAs($this->manager)
            ->get('/dashboard/weekly-summary/export?week='.$this->monday->toDateString())
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"Total sales",300.00', $csv);
        $this->assertStringContainsString('Shahzad', $csv);
    }
}
