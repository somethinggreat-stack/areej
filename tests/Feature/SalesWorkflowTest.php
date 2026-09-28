<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The chain the business actually runs on:
 * enquiry → quote → acceptance → job → invoice.
 */
class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);
    }

    private function enquiry(array $overrides = []): Enquiry
    {
        return Enquiry::create(array_merge([
            'name' => 'Nadia Begum',
            'phone' => '07700 900111',
            'event_type' => 'Weddings',
            'event_date' => today()->addMonths(3),
            'guests' => 300,
            'venue' => 'Grand Station',
            'service_style' => 'Full buffet setup',
            'status' => 'new',
        ], $overrides));
    }

    private function dish(array $overrides = []): Dish
    {
        return Dish::create(array_merge([
            'name_en' => 'Chicken Biryani',
            'course' => 'mains',
            'price_per_head' => 850,
            'portion_grams' => 350,
            'is_active' => true,
        ], $overrides));
    }

    /* ------------------------------------------------------------ the chain */

    public function test_an_enquiry_becomes_a_quote_carrying_everything_across(): void
    {
        $enquiry = $this->enquiry();

        $this->actingAs($this->user)
            ->post("/dashboard/enquiries/{$enquiry->id}/quote")
            ->assertRedirect();

        $quote = Quote::firstOrFail();

        $this->assertSame($enquiry->id, $quote->enquiry_id);
        $this->assertSame('Nadia Begum', $quote->customer_name);
        $this->assertSame(300, $quote->guests);
        $this->assertSame('full_buffet', $quote->service_style, 'Website wording maps to the internal vocabulary.');
        $this->assertSame('quoted', $enquiry->fresh()->status);
        $this->assertNotNull($quote->valid_until, 'Validity comes from settings, not a hard-coded number.');
    }

    public function test_the_quote_total_always_matches_its_lines(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 100, 'status' => 'draft']);
        $dish = $this->dish();

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'dish_id' => $dish->id,
            'quantity' => 100,
            'unit_price' => 8.50,
        ]);

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Waiting staff, 4 hours',
            'quantity' => 4,
            'unit_price' => 60,
        ]);

        $quote->refresh();

        $this->assertSame(85000 + 24000, $quote->subtotal);
        $this->assertSame(109000, $quote->total);
        $this->assertSame('Chicken Biryani', $quote->lines->first()->description, 'A dish line names itself.');
    }

    public function test_a_discount_comes_off_the_total_but_not_the_subtotal(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 100, 'status' => 'draft', 'discount_percent' => 10]);

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Buffet', 'quantity' => 1, 'unit_price' => 1000,
        ]);

        $quote->refresh();

        $this->assertSame(100000, $quote->subtotal);
        $this->assertSame(90000, $quote->total);
        $this->assertSame(10000, $quote->discountAmount());
    }

    public function test_an_empty_quote_cannot_be_sent(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 50, 'status' => 'draft']);

        $this->actingAs($this->user)
            ->post("/dashboard/quotes/{$quote->id}/send")
            ->assertSessionHasErrors('form');

        $this->assertSame('draft', $quote->fresh()->status);
    }

    public function test_accepting_a_quote_creates_the_job_and_carries_the_lines(): void
    {
        $enquiry = $this->enquiry();
        $dish = $this->dish();

        $this->actingAs($this->user)->post("/dashboard/enquiries/{$enquiry->id}/quote");
        $quote = Quote::firstOrFail();

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'dish_id' => $dish->id,
            'quantity' => 300,
            'unit_price' => 8.50,
        ]);

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/accept")->assertRedirect();

        $quote->refresh();
        $order = $quote->order;

        $this->assertNotNull($order);
        $this->assertSame('accepted', $quote->status);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(255000, $order->total_amount);
        $this->assertSame($enquiry->id, $order->enquiry_id, 'The trail from first contact stays unbroken.');
        $this->assertSame('won', $enquiry->fresh()->status);

        // The priced line becomes both an invoice line and a kitchen instruction.
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, $order->dishes()->count());
        $this->assertSame(300, $order->dishes()->first()->guests);
    }

    public function test_the_deposit_is_taken_from_settings(): void
    {
        Setting::put('deposit_percent', 30, 'int', 'sales');

        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 100, 'status' => 'draft']);
        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Buffet', 'quantity' => 1, 'unit_price' => 1000,
        ]);

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/accept");

        $this->assertSame(30000, $quote->fresh()->order->deposit_due);
    }

    public function test_accepting_twice_does_not_create_a_second_job(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 50, 'status' => 'draft']);
        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Buffet', 'quantity' => 1, 'unit_price' => 500,
        ]);

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/accept");
        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/accept");

        $this->assertSame(1, Order::count());
    }

    public function test_a_decided_quote_can_no_longer_be_edited(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 50, 'status' => 'accepted']);

        $this->actingAs($this->user)
            ->patch("/dashboard/quotes/{$quote->id}", [
                'customer_name' => 'Changed',
                'guests' => 60,
                'service_style' => 'not_set',
            ])
            ->assertStatus(422);

        $this->assertSame('Test', $quote->fresh()->customer_name);
    }

    public function test_an_accepted_quote_cannot_be_deleted(): void
    {
        $quote = Quote::create(['customer_name' => 'Test', 'guests' => 50, 'status' => 'accepted']);

        $this->actingAs($this->user)
            ->delete("/dashboard/quotes/{$quote->id}")
            ->assertSessionHasErrors('form');

        $this->assertNotSoftDeleted('quotes', ['id' => $quote->id]);
    }

    public function test_declining_closes_the_enquiry_too(): void
    {
        $enquiry = $this->enquiry();
        $this->actingAs($this->user)->post("/dashboard/enquiries/{$enquiry->id}/quote");
        $quote = Quote::firstOrFail();

        $this->actingAs($this->user)->post("/dashboard/quotes/{$quote->id}/decline");

        $this->assertSame('declined', $quote->fresh()->status);
        $this->assertSame('lost', $enquiry->fresh()->status);
    }

    /* -------------------------------------------------------------- money */

    public function test_a_job_with_money_taken_cannot_be_deleted(): void
    {
        $order = Order::create([
            'customer_name' => 'Test', 'event_date' => today(), 'total_amount' => 50000,
            'service_style' => 'not_set', 'status' => 'confirmed',
        ]);

        $this->actingAs($this->user)->post("/dashboard/orders/{$order->id}/payments", [
            'amount' => 100, 'method' => 'cash', 'kind' => 'deposit', 'paid_on' => today()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete("/dashboard/orders/{$order->id}")
            ->assertSessionHasErrors('form');

        $this->assertNotSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_an_invoice_shows_the_same_figures_as_the_job(): void
    {
        $order = Order::create([
            'customer_name' => 'Nadia Begum', 'event_date' => today(), 'total_amount' => 120000,
            'service_style' => 'not_set', 'status' => 'confirmed', 'guests' => 100,
        ]);

        $this->actingAs($this->user)->post("/dashboard/orders/{$order->id}/payments", [
            'amount' => 500, 'method' => 'cash', 'kind' => 'deposit', 'paid_on' => today()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get("/dashboard/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertSee('£1,200.00')   // total
            ->assertSee('£500.00')     // paid
            ->assertSee('£700.00');    // outstanding
    }

    public function test_sales_staff_cannot_reach_pay_or_settings(): void
    {
        $sales = User::factory()->create([
            'role_id' => Role::where('slug', 'sales')->value('id'),
            'is_active' => true,
        ]);

        $this->actingAs($sales)->get('/dashboard/quotes')->assertOk();
        $this->actingAs($sales)->get('/dashboard/orders')->assertOk();

        $this->actingAs($sales)->get('/dashboard/timesheets')->assertForbidden();
        $this->actingAs($sales)->get('/dashboard/settings')->assertForbidden();
        $this->actingAs($sales)->get('/dashboard/inventory')->assertForbidden();
    }
}
