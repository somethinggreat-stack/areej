<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\IngredientPlanner;
use App\Services\StockLedger;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recipes → guest count → shopping list → stock deduction.
 *
 * This is the chain that makes the system worth having, and the one where a
 * quiet arithmetic mistake would put the wrong amount of food on a wedding.
 */
class KitchenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private InventoryItem $rice;

    private InventoryItem $chicken;

    private Dish $biryani;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'name_en' => 'Food', 'slug' => 'food', 'kind' => 'food', 'sort_order' => 1,
        ]);

        $this->rice = InventoryItem::create([
            'inventory_category_id' => $category->id, 'name_en' => 'Basmati Rice',
            'unit' => 'kg', 'current_quantity' => 0, 'reorder_level' => 20,
            'unit_cost' => 2.00, 'count_frequency' => 'weekly', 'is_active' => true,
        ]);

        $this->chicken = InventoryItem::create([
            'inventory_category_id' => $category->id, 'name_en' => 'Chicken',
            'unit' => 'kg', 'current_quantity' => 0, 'reorder_level' => 30,
            'unit_cost' => 3.50, 'count_frequency' => 'daily', 'is_active' => true,
        ]);

        $ledger = app(StockLedger::class);
        $ledger->record($this->rice, StockMovement::TYPE_OPENING, 100, $this->user);
        $ledger->record($this->chicken, StockMovement::TYPE_OPENING, 40, $this->user);

        $this->biryani = Dish::create([
            'name_en' => 'Chicken Biryani', 'course' => 'mains',
            'price_per_head' => 850, 'portion_grams' => 350, 'is_active' => true,
        ]);

        // Per 100 guests.
        $this->biryani->ingredients()->create(['inventory_item_id' => $this->rice->id, 'quantity_per_100' => 14]);
        $this->biryani->ingredients()->create(['inventory_item_id' => $this->chicken->id, 'quantity_per_100' => 18]);
    }

    private function job(int $guests = 300): Order
    {
        $order = Order::create([
            'customer_name' => 'Nadia Begum', 'event_date' => today()->addWeek(),
            'guests' => $guests, 'service_style' => 'not_set', 'status' => 'confirmed',
            'total_amount' => 255000,
        ]);

        $order->dishes()->create(['dish_id' => $this->biryani->id, 'guests' => $guests]);

        return $order->fresh();
    }

    /* ------------------------------------------------------- the forecast */

    public function test_a_recipe_scales_to_the_guest_count(): void
    {
        $rows = app(IngredientPlanner::class)->requirementsFor($this->job(300));

        $rice = $rows->firstWhere(fn ($r) => $r['item']->id === $this->rice->id);
        $chicken = $rows->firstWhere(fn ($r) => $r['item']->id === $this->chicken->id);

        $this->assertSame(42.0, $rice['required'], '14kg per 100 × 3 = 42kg');
        $this->assertSame(54.0, $chicken['required'], '18kg per 100 × 3 = 54kg');
    }

    public function test_a_shortfall_is_the_gap_between_need_and_shelf(): void
    {
        $rows = app(IngredientPlanner::class)->requirementsFor($this->job(300));

        $chicken = $rows->firstWhere(fn ($r) => $r['item']->id === $this->chicken->id);
        $rice = $rows->firstWhere(fn ($r) => $r['item']->id === $this->rice->id);

        $this->assertSame(14.0, $chicken['shortfall'], '54 needed, 40 on hand');
        $this->assertSame(0.0, $rice['shortfall'], '42 needed, 100 on hand — nothing short');
    }

    public function test_two_dishes_sharing_an_ingredient_are_added_together(): void
    {
        $karahi = Dish::create([
            'name_en' => 'Karahi', 'course' => 'mains', 'price_per_head' => 900,
            'portion_grams' => 300, 'is_active' => true,
        ]);
        $karahi->ingredients()->create(['inventory_item_id' => $this->chicken->id, 'quantity_per_100' => 20]);

        $order = $this->job(100);
        $order->dishes()->create(['dish_id' => $karahi->id, 'guests' => 100]);

        $rows = app(IngredientPlanner::class)->requirementsFor($order->fresh());
        $chicken = $rows->firstWhere(fn ($r) => $r['item']->id === $this->chicken->id);

        $this->assertSame(38.0, $chicken['required'], '18 + 20 for 100 guests each');
        $this->assertCount(2, $chicken['dishes'], 'Both dishes are named against the ingredient');
    }

    public function test_food_cost_uses_the_current_unit_price(): void
    {
        $rows = app(IngredientPlanner::class)->requirementsFor($this->job(100));

        // 14kg rice @ £2 = £28, 18kg chicken @ £3.50 = £63
        $this->assertSame(91.0, round($rows->sum('cost'), 2));
    }

    public function test_a_dish_with_no_recipe_forecasts_nothing_rather_than_zero(): void
    {
        $plain = Dish::create([
            'name_en' => 'Plain Rice', 'course' => 'sides', 'price_per_head' => 250,
            'portion_grams' => 250, 'is_active' => true,
        ]);

        $order = Order::create([
            'customer_name' => 'Test', 'event_date' => today(), 'guests' => 100,
            'service_style' => 'not_set', 'status' => 'confirmed',
        ]);
        $order->dishes()->create(['dish_id' => $plain->id, 'guests' => 100]);

        $this->assertNull(
            app(IngredientPlanner::class)->estimatedFoodCost($order->fresh()),
            'No recipe means unknown cost, not free.'
        );
    }

    /* ------------------------------------------------------- the deduction */

    public function test_deducting_takes_the_planned_amounts_off_the_shelf(): void
    {
        $order = $this->job(100);

        $result = app(IngredientPlanner::class)->deduct($order, $this->user);

        $this->assertSame(2, $result['deducted']);
        $this->assertSame([], $result['blocked']);
        $this->assertSame('86.000', $this->rice->fresh()->current_quantity, '100 − 14');
        $this->assertSame('22.000', $this->chicken->fresh()->current_quantity, '40 − 18');

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->rice->id,
            'type' => StockMovement::TYPE_EVENT_USAGE,
            'quantity_change' => -14,
        ]);
    }

    public function test_a_shortfall_blocks_the_deduction_rather_than_going_negative(): void
    {
        $order = $this->job(300);   // needs 54kg chicken, only 40 on hand

        $result = app(IngredientPlanner::class)->deduct($order, $this->user);

        $this->assertSame(0, $result['deducted']);
        $this->assertContains('Chicken', $result['blocked']);
        $this->assertSame('40.000', $this->chicken->fresh()->current_quantity, 'Nothing moved');
        $this->assertSame('100.000', $this->rice->fresh()->current_quantity, 'Not even the item that was in stock');
    }

    public function test_a_job_cannot_be_deducted_twice(): void
    {
        $order = $this->job(100);

        app(IngredientPlanner::class)->deduct($order, $this->user);
        $second = app(IngredientPlanner::class)->deduct($order->fresh(), $this->user);

        $this->assertSame(0, $second['deducted']);
        $this->assertSame('86.000', $this->rice->fresh()->current_quantity, 'Still only deducted once');
    }

    public function test_the_deduct_route_refuses_and_explains_when_short(): void
    {
        $order = $this->job(300);

        $this->actingAs($this->user)
            ->post("/dashboard/orders/{$order->id}/deduct")
            ->assertSessionHasErrors('form');

        $this->assertSame('40.000', $this->chicken->fresh()->current_quantity);
    }

    public function test_a_deducted_dish_cannot_be_removed_from_the_job(): void
    {
        $order = $this->job(100);
        app(IngredientPlanner::class)->deduct($order, $this->user);

        $orderDish = $order->dishes()->first();

        $this->actingAs($this->user)
            ->delete("/dashboard/orders/dishes/{$orderDish->id}")
            ->assertSessionHasErrors('form');

        $this->assertDatabaseHas('order_dishes', ['id' => $orderDish->id]);
    }

    /* ----------------------------------------------------------- the menu */

    public function test_adding_a_dish_twice_corrects_the_count_rather_than_doubling_it(): void
    {
        $order = $this->job(100);

        $this->actingAs($this->user)->post("/dashboard/orders/{$order->id}/dishes", [
            'dish_id' => $this->biryani->id,
            'guests' => 250,
        ]);

        $this->assertSame(1, $order->dishes()->count());
        $this->assertSame(250, $order->dishes()->first()->guests);
    }

    public function test_an_ingredient_added_twice_corrects_the_recipe(): void
    {
        $this->actingAs($this->user)->post("/dashboard/dishes/{$this->biryani->id}/ingredients", [
            'inventory_item_id' => $this->rice->id,
            'quantity_per_100' => 16,
        ]);

        $this->assertSame(2, $this->biryani->ingredients()->count());
        $this->assertSame('16.000', $this->biryani->ingredients()->where('inventory_item_id', $this->rice->id)->first()->quantity_per_100);
    }

    public function test_dish_margin_is_unknown_without_a_recipe(): void
    {
        $plain = Dish::create([
            'name_en' => 'Naan', 'course' => 'sides', 'price_per_head' => 120,
            'portion_grams' => 120, 'is_active' => true,
        ]);

        $this->assertNull($plain->ingredientCostPerHead());
        $this->assertNull($plain->marginPercent());

        // 14kg rice @£2 + 18kg chicken @£3.50 = £91 per 100 = 91p per head
        $this->assertSame(91, $this->biryani->ingredientCostPerHead());
        $this->assertSame(89.3, $this->biryani->marginPercent());
    }

    public function test_a_dish_on_an_upcoming_job_cannot_be_deleted(): void
    {
        $this->job(100);

        $this->actingAs($this->user)
            ->delete("/dashboard/dishes/{$this->biryani->id}")
            ->assertSessionHasErrors('form');

        $this->assertNotSoftDeleted('dishes', ['id' => $this->biryani->id]);
    }

    public function test_the_menu_lists_courses_in_menu_order_not_alphabetically(): void
    {
        foreach ([['Samosa', 'appetisers'], ['Kebab', 'starters'], ['Gajrella', 'desserts'], ['Tea', 'drinks']] as [$name, $course]) {
            Dish::create([
                'name_en' => $name, 'course' => $course, 'price_per_head' => 100,
                'portion_grams' => 100, 'is_active' => true,
            ]);
        }

        $order = Dish::active()->inMenuOrder()->pluck('course')->unique()->values()->all();

        $this->assertSame(['appetisers', 'starters', 'mains', 'desserts', 'drinks'], $order);
    }

    /* ------------------------------------------------------------- tasks */

    public function test_the_standard_checklist_is_added_once(): void
    {
        $order = $this->job(100);

        $this->actingAs($this->user)->post("/dashboard/orders/{$order->id}/tasks/standard");
        $first = $order->tasks()->count();

        $this->actingAs($this->user)->post("/dashboard/orders/{$order->id}/tasks/standard");

        $this->assertGreaterThan(0, $first);
        $this->assertSame($first, $order->tasks()->count(), 'A second click does not duplicate the list');
    }

    public function test_a_task_is_due_relative_to_the_serving_time(): void
    {
        $order = Order::create([
            'customer_name' => 'Test', 'event_date' => today()->addDays(2), 'serve_time' => '18:00',
            'service_style' => 'not_set', 'status' => 'confirmed', 'guests' => 100,
        ]);

        $task = $order->tasks()->create(['title' => 'Marinate', 'stage' => 'prep', 'due_offset_hours' => 18]);

        $this->assertSame(
            today()->addDays(2)->setTime(0, 0)->toDateString(),
            $task->dueAt()->addHours(18)->toDateString()
        );
        $this->assertSame('00:00', $task->dueAt()->format('H:i'), '18:00 serving less 18 hours');
    }
}
