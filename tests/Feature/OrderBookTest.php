<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The simple order book: a week of notebook orders typed in on Monday, each
 * with free-text items, an amount, what was paid and what is still owed.
 */
class OrderBookTest extends TestCase
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

    /** @return array<string, mixed> */
    private function order(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Shahzad',
            'phone' => '07566 633366',
            'event_date' => today()->subDays(3)->toDateString(),
            'order_status' => 'completed',
            'items' => [
                ['description' => 'Roast + Thigh', 'quantity' => 30, 'unit' => 'pieces', 'price' => 2.50],
                ['description' => 'Fish Panga', 'quantity' => 60, 'unit' => 'pieces', 'price' => 2],
                ['description' => 'Chicken pulao', 'quantity' => 5, 'unit' => 'kg', 'price' => ''],
                ['description' => '', 'quantity' => '', 'unit' => 'portion', 'price' => ''],
            ],
            'total' => '',
            'paid_now' => '100',
            'paid_method' => 'cash',
            'paid_on' => today()->toDateString(),
        ], $overrides);
    }

    public function test_a_whole_order_goes_in_from_one_page(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order())
            ->assertSessionHasNoErrors();

        $order = Order::with(['items', 'payments'])->firstOrFail();

        $this->assertSame('completed', $order->status);
        $this->assertCount(3, $order->items, 'Blank rows are ignored.');
        $this->assertSame('pieces', $order->items->first()->unit);
        $this->assertSame(19500, $order->total_amount, '30 × £2.50 + 60 × £2; the unpriced line adds nothing.');
        $this->assertSame(10000, $order->paidAmount());
        $this->assertSame(9500, $order->balanceAmount());
    }

    public function test_a_typed_order_amount_wins_over_the_items(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['total' => '300', 'paid_now' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(30000, Order::firstOrFail()->total_amount);
        $this->assertSame(0, OrderPayment::count());
    }

    public function test_save_and_add_the_next_one_keeps_the_date(): void
    {
        $date = today()->subDays(5)->toDateString();

        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['event_date' => $date, 'add_another' => 1]))
            ->assertRedirect(route('order-book.create', ['date' => $date]));
    }

    public function test_an_order_can_be_changed_later_with_its_items_and_a_payment(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order());
        $order = Order::firstOrFail();

        $this->actingAs($this->manager)->put("/dashboard/order-book/{$order->id}", $this->order([
            'items' => [['description' => 'Fish Panga', 'quantity' => 80, 'unit' => 'pieces', 'price' => 2]],
            'paid_now' => '60',
        ]))->assertSessionHasNoErrors();

        $order->refresh()->load(['items', 'payments']);
        $this->assertCount(1, $order->items);
        $this->assertSame(16000, $order->total_amount);
        $this->assertSame(16000, $order->paidAmount(), '£100 first, then £60.');
        $this->assertTrue($order->isPaid());

        $this->actingAs($this->manager)->get("/dashboard/order-book/{$order->id}/edit")
            ->assertOk()
            ->assertSee('Fish Panga')
            ->assertSee('Payment history');
    }

    public function test_the_order_list_splits_pending_completed_owing_and_paid(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['customer_name' => 'Owes Money']));
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['customer_name' => 'Paid Up', 'phone' => '07000 111222', 'total' => '100']));
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['customer_name' => 'Still Pending', 'phone' => '07000 333444', 'order_status' => 'pending']));

        $this->actingAs($this->manager)->get('/dashboard/orders?view=owing')
            ->assertSee('Owes Money')->assertDontSee('Paid Up');
        $this->actingAs($this->manager)->get('/dashboard/orders?view=paid')
            ->assertSee('Paid Up')->assertDontSee('Owes Money');
        $this->actingAs($this->manager)->get('/dashboard/orders?view=pending')
            ->assertSee('Still Pending')->assertDontSee('Paid Up');
    }

    public function test_customers_add_up_their_orders_by_phone_number(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order());
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order(['phone' => '07566633366', 'total' => '50', 'paid_now' => '']));

        $this->actingAs($this->manager)->get('/dashboard/customers')
            ->assertOk()
            ->assertSee('Shahzad')
            ->assertSee('£145.00', false);

        $this->actingAs($this->manager)->get('/dashboard/customers/tel-07566633366')
            ->assertOk()
            ->assertSee('Payment history')
            ->assertSee('£245.00', false);
    }

    public function test_payments_list_every_payment_in_the_dates(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/order-book', $this->order());

        $this->actingAs($this->manager)->get('/dashboard/payments')
            ->assertOk()
            ->assertSee('Shahzad')
            ->assertSee('£100.00', false);
    }

    public function test_the_order_book_is_for_the_managing_team(): void
    {
        $sales = User::factory()->create(['role_id' => Role::where('slug', Role::SALES)->value('id'), 'is_active' => true]);

        $this->actingAs($sales)->get('/dashboard/order-book/new')->assertForbidden();
        $this->actingAs($sales)->get('/dashboard/customers')->assertForbidden();
    }
}
