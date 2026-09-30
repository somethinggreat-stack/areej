<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WasteLog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Waste sorted by a simple type, including cooked food that was never a
 * stock item.
 */
class WasteByTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private InventoryItem $chicken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->manager = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);

        $category = InventoryCategory::create(['name_en' => 'Meat & Poultry', 'slug' => 'meat-poultry', 'kind' => 'food', 'sort_order' => 1]);

        $this->chicken = InventoryItem::create([
            'inventory_category_id' => $category->id,
            'name_en' => 'Chicken thighs',
            'unit' => 'kg',
            'current_quantity' => 20,
            'reorder_level' => 5,
            'unit_cost' => 4.00,
            'count_frequency' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_the_type_is_worked_out_from_the_stock_item_when_left_blank(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/waste', [
            'inventory_item_id' => $this->chicken->id,
            'quantity' => 2,
            'reason' => 'spoilage',
            'wasted_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('meat_poultry', WasteLog::firstOrFail()->type);
        $this->assertSame('18.000', $this->chicken->fresh()->current_quantity);
    }

    public function test_leftover_cooked_food_is_recorded_without_touching_stock(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/waste', [
            'type' => 'cooked_food',
            'description' => 'Chicken biryani left over',
            'quantity' => 3,
            'unit' => 'kg',
            'reason' => 'over_production',
            'wasted_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $log = WasteLog::firstOrFail();
        $this->assertNull($log->inventory_item_id);
        $this->assertSame('Chicken biryani left over', $log->itemName());
        $this->assertSame('KG', $log->unitLabel());
        $this->assertSame(0, StockMovement::count());

        $this->actingAs($this->manager)->delete("/dashboard/waste/{$log->id}")->assertSessionHas('status', 'Waste entry removed.');
        $this->assertSoftDeleted($log);
    }

    public function test_waste_needs_a_stock_item_or_a_name(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/waste', [
            'type' => 'other',
            'quantity' => 1,
            'reason' => 'other',
            'wasted_on' => today()->toDateString(),
        ])->assertSessionHasErrors(['inventory_item_id', 'description']);
    }

    public function test_the_waste_page_sums_up_by_type_and_filters_to_one(): void
    {
        WasteLog::create(['type' => 'cooked_food', 'description' => 'Leftover korma', 'quantity' => 2, 'unit' => 'kg', 'reason' => 'over_production', 'wasted_on' => today()]);
        WasteLog::create(['type' => 'dairy', 'description' => 'Sour yoghurt', 'quantity' => 1, 'unit' => 'kg', 'reason' => 'spoilage', 'wasted_on' => today()]);

        $this->actingAs($this->manager)->get('/dashboard/waste')
            ->assertOk()
            ->assertSee('By type')
            ->assertSee('Cooked food')
            ->assertSee('Leftover korma')
            ->assertSee('Sour yoghurt');

        $this->actingAs($this->manager)->get('/dashboard/waste?type=dairy')
            ->assertOk()
            ->assertSee('Sour yoghurt')
            ->assertDontSee('Leftover korma');
    }
}
