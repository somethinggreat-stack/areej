<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\InventoryStarterSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quantities in KG, Portion, Pieces or Number everywhere, and prices kept to
 * the managing team.
 */
class UnitsAndPrivatePricesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $slug)->value('id'), 'is_active' => true]);
    }

    private function dish(): Dish
    {
        return Dish::create(['name_en' => 'Chicken Biryani', 'course' => 'mains', 'price_per_head' => 850, 'portion_grams' => 350, 'is_active' => true]);
    }

    public function test_units_read_as_words(): void
    {
        $this->assertSame('Pieces', unit_label('pieces'));
        $this->assertSame('KG', unit_label('KG'));
        $this->assertSame('trays', unit_label('trays'), 'An old free-text unit is shown as written.');
        $this->assertSame(['kg', 'portion', 'pieces', 'number'], array_keys(catering_units()));
        $this->assertArrayHasKey('litres', catering_units(true));
    }

    public function test_a_quote_line_carries_its_unit_through_to_the_invoice(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $quote = Quote::create(['customer_name' => 'Latif', 'guests' => 50, 'status' => 'draft']);

        $this->actingAs($manager)->post("/dashboard/quotes/{$quote->id}/lines", [
            'description' => 'Fish Panga', 'quantity' => 60, 'unit' => 'pieces', 'unit_price' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame('pieces', $quote->lines()->first()->unit);

        $this->actingAs($manager)->post("/dashboard/quotes/{$quote->id}/accept");
        $order = Order::firstOrFail();

        $this->actingAs($manager)->get("/dashboard/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertSee('60 Pieces');
    }

    public function test_a_dish_line_defaults_to_portions(): void
    {
        $quote = Quote::create(['customer_name' => 'Latif', 'guests' => 50, 'status' => 'draft']);

        $this->actingAs($this->userWithRole(Role::MANAGEMENT))->post("/dashboard/quotes/{$quote->id}/lines", [
            'dish_id' => $this->dish()->id, 'quantity' => 50, 'unit_price' => 8.5,
        ]);

        $this->assertSame('portion', $quote->lines()->first()->unit);
    }

    public function test_stock_items_pick_their_unit_from_the_list(): void
    {
        $this->seed(InventoryStarterSeeder::class);

        $this->actingAs($this->userWithRole(Role::MANAGEMENT))->get('/dashboard/inventory/create')
            ->assertOk()
            ->assertSee('<option value="litres"', false);
    }

    public function test_dish_prices_and_margins_are_for_the_managing_team_only(): void
    {
        $dish = $this->dish();

        $this->actingAs($this->userWithRole(Role::MANAGER))->get("/dashboard/dishes/{$dish->id}")
            ->assertOk()
            ->assertDontSee('£8.50', false)
            ->assertDontSee('Gross margin');

        $this->actingAs($this->userWithRole(Role::MANAGER))->get('/dashboard/dishes')
            ->assertDontSee('£8.50', false);

        $this->actingAs($this->userWithRole(Role::MANAGEMENT))->get("/dashboard/dishes/{$dish->id}")
            ->assertSee('£8.50', false);
    }

    public function test_the_kitchen_saving_a_dish_never_wipes_its_price(): void
    {
        $dish = $this->dish();

        $this->actingAs($this->userWithRole(Role::MANAGER))->patch("/dashboard/dishes/{$dish->id}", [
            'name_en' => 'Chicken Biryani', 'course' => 'mains', 'portion_grams' => 400, 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $dish->refresh();
        $this->assertSame(400, $dish->portion_grams);
        $this->assertSame(850, $dish->price_per_head);
    }
}
