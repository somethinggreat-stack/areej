<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockLedger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->manager = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'name_en' => 'Rice & Grains',
            'slug' => 'rice-grains',
            'kind' => 'food',
            'sort_order' => 1,
        ]);

        $this->item = InventoryItem::create([
            'inventory_category_id' => $category->id,
            'supplier_id' => Supplier::create(['name' => 'Test Wholesale', 'is_active' => true])->id,
            'name_en' => 'Basmati Rice',
            'unit' => 'kg',
            'current_quantity' => 100,
            'reorder_level' => 40,
            'reorder_quantity' => 80,
            'unit_cost' => 2.00,
            'count_frequency' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_the_inventory_list_loads(): void
    {
        $this->actingAs($this->manager)
            ->get('/dashboard/inventory')
            ->assertOk()
            ->assertSee('Basmati Rice');
    }

    /**
     * The bug this guards: rtrim() with no decimal point eats real trailing
     * zeros, turning 100 into 1 and -30 into -3.
     */
    public function test_whole_numbers_keep_their_trailing_zeros(): void
    {
        $this->assertSame('100', qty(100));
        $this->assertSame('-30', qty(-30.0));
        $this->assertSame('20', qty('20'));
        $this->assertSame('180.5', qty('180.500'));
        $this->assertSame('0.75', qty(0.75));
        $this->assertSame('0', qty(null));

        $this->actingAs($this->manager)
            ->get('/dashboard/inventory')
            ->assertSee('100 kg');
    }

    public function test_stock_only_moves_through_the_ledger(): void
    {
        $ledger = app(StockLedger::class);

        $ledger->record($this->item, StockMovement::TYPE_PURCHASE, 25, $this->manager);

        $this->assertSame('125.000', $this->item->fresh()->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->item->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity_change' => 25,
            'quantity_after' => 125,
        ]);
    }

    public function test_a_count_that_matches_records_no_adjustment(): void
    {
        $movement = app(StockLedger::class)->setTo($this->item, 100.0, $this->manager);

        $this->assertNull($movement, 'A count agreeing with the system should not write a movement.');
    }

    public function test_a_manual_adjustment_cannot_take_stock_below_zero(): void
    {
        $this->actingAs($this->manager)
            ->from('/dashboard/inventory/'.$this->item->id)
            ->post("/dashboard/inventory/{$this->item->id}/adjust", [
                'quantity_change' => -500,
                'notes' => 'Trying to go negative',
            ])
            ->assertSessionHasErrors('quantity_change');

        $this->assertSame('100.000', $this->item->fresh()->current_quantity);
    }

    public function test_a_stock_count_builds_a_sheet_and_corrects_stock_on_submission(): void
    {
        $this->actingAs($this->manager)
            ->post('/dashboard/stock-counts', [
                'scope' => 'weekly',
                'counted_on' => today()->toDateString(),
            ])
            ->assertRedirect();

        $count = StockCount::firstOrFail();
        $line = $count->lines()->firstOrFail();

        $this->assertSame('draft', $count->status);
        $this->assertSame('100.000', $line->expected_quantity, 'Expected quantity is frozen when the sheet is built.');

        // Nothing should move while the sheet is still being walked.
        $this->actingAs($this->manager)
            ->patch("/dashboard/stock-counts/{$count->id}", [
                'line_id' => $line->id,
                'counted_quantity' => 88,
            ]);

        $this->assertSame('100.000', $this->item->fresh()->current_quantity);

        $this->actingAs($this->manager)
            ->post("/dashboard/stock-counts/{$count->id}/complete")
            ->assertRedirect();

        $this->assertSame('88.000', $this->item->fresh()->current_quantity);
        $this->assertSame('completed', $count->fresh()->status);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->item->id,
            'type' => StockMovement::TYPE_COUNT,
            'quantity_change' => -12,
        ]);
    }

    public function test_an_uncounted_line_is_left_alone_rather_than_treated_as_zero(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/stock-counts', [
            'scope' => 'weekly',
            'counted_on' => today()->toDateString(),
        ]);

        $count = StockCount::firstOrFail();
        $line = $count->lines()->firstOrFail();

        $this->actingAs($this->manager)->patch("/dashboard/stock-counts/{$count->id}", [
            'line_id' => $line->id,
            'counted_quantity' => 88,
        ]);

        // A second item nobody counted.
        $untouched = InventoryItem::create([
            'inventory_category_id' => $this->item->inventory_category_id,
            'name_en' => 'Chana Dal',
            'unit' => 'kg',
            'current_quantity' => 0,
            'reorder_level' => 5,
            'count_frequency' => 'weekly',
            'is_active' => true,
        ]);

        app(StockLedger::class)->record($untouched, StockMovement::TYPE_OPENING, 50, $this->manager);

        $this->actingAs($this->manager)->post("/dashboard/stock-counts/{$count->id}/complete");

        $this->assertSame('50.000', $untouched->fresh()->current_quantity);
    }

    public function test_waste_takes_stock_off_the_shelf(): void
    {
        $this->actingAs($this->manager)
            ->post('/dashboard/waste', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 10,
                'reason' => 'spoilage',
                'wasted_on' => today()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('90.000', $this->item->fresh()->current_quantity);
        $this->assertDatabaseHas('waste_logs', [
            'inventory_item_id' => $this->item->id,
            'reason' => 'spoilage',
            // Cost is frozen at the time of logging.
            'unit_cost' => 2.00,
        ]);
    }

    public function test_waste_beyond_what_is_on_hand_is_refused(): void
    {
        $this->actingAs($this->manager)
            ->post('/dashboard/waste', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 5000,
                'reason' => 'spoilage',
                'wasted_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame('100.000', $this->item->fresh()->current_quantity);
    }

    public function test_low_stock_appears_on_the_reorder_list(): void
    {
        app(StockLedger::class)->record($this->item, StockMovement::TYPE_WASTE, -70, $this->manager);

        $this->assertTrue($this->item->fresh()->isLowStock());

        $this->actingAs($this->manager)
            ->get('/dashboard/purchase-orders')
            ->assertOk()
            ->assertSee('Test Wholesale')
            ->assertSee('Basmati Rice');
    }

    public function test_a_delivery_only_moves_stock_when_it_is_received(): void
    {
        $this->actingAs($this->manager)->post('/dashboard/purchase-orders', [
            'supplier_id' => $this->item->supplier_id,
            'items' => [
                ['inventory_item_id' => $this->item->id, 'quantity_ordered' => 50, 'unit_cost' => 2.10],
            ],
        ]);

        $order = PurchaseOrder::firstOrFail();

        // Ordering is a promise, not an arrival.
        $this->assertSame('100.000', $this->item->fresh()->current_quantity);

        // A short delivery leaves the order open.
        $this->actingAs($this->manager)->post("/dashboard/purchase-orders/{$order->id}/receive", [
            'received_on' => today()->toDateString(),
            'lines' => [
                ['id' => $order->lines()->first()->id, 'quantity_received' => 30, 'unit_cost' => 2.10],
            ],
        ]);

        $this->assertSame('130.000', $this->item->fresh()->current_quantity);
        $this->assertSame('part_received', $order->fresh()->status);
        $this->assertSame('2.10', $this->item->fresh()->unit_cost, 'A delivery is the freshest price we have.');
    }

    /**
     * Access is a ladder, so a kitchen manager reaches everything below their
     * level — including raising a purchase order, which is useful because they
     * are the ones who know what has run out. What the ladder must never leak
     * is money and pay.
     */
    public function test_the_kitchen_manager_can_run_the_kitchen(): void
    {
        $kitchen = $this->kitchenManager();

        foreach (['/dashboard/inventory', '/dashboard/stock-counts', '/dashboard/waste',
            '/dashboard/equipment', '/dashboard/dishes', '/dashboard/prep',
            '/dashboard/purchase-orders'] as $path) {
            $this->actingAs($kitchen)->get($path)->assertOk();
        }
    }

    public function test_the_kitchen_manager_never_sees_pay_or_settings(): void
    {
        $kitchen = $this->kitchenManager();

        foreach (['/dashboard/timesheets', '/dashboard/attendance', '/dashboard/staff',
            '/dashboard/settings', '/dashboard/activity', '/dashboard/expenses',
            '/dashboard/reports'] as $path) {
            $this->actingAs($kitchen)->get($path)->assertForbidden();
        }
    }

    /**
     * The stronger guarantee: even on a page they are allowed to open, no
     * money reaches the HTML. Route gating alone would still leak a cost
     * column into a page they can see.
     */
    public function test_costs_never_reach_a_kitchen_managers_screen(): void
    {
        $kitchen = $this->kitchenManager();

        $this->assertFalse($kitchen->canSeeFinancials());

        $body = $this->actingAs($kitchen)->get('/dashboard/inventory')->getContent();
        $this->assertStringNotContainsString('£'.number_format($this->item->stockValue(), 2), $body);

        $poBody = $this->actingAs($kitchen)->get('/dashboard/purchase-orders')->getContent();
        $this->assertStringNotContainsString('Unit cost', $poBody);
    }

    public function test_only_management_can_add_or_price_stock(): void
    {
        $kitchen = $this->kitchenManager();

        $this->actingAs($kitchen)->get('/dashboard/inventory/'.$this->item->id.'/edit')->assertForbidden();
        $this->actingAs($kitchen)->post('/dashboard/inventory', [])->assertForbidden();
        $this->actingAs($kitchen)->delete('/dashboard/inventory/'.$this->item->id)->assertForbidden();
    }

    private function kitchenManager(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGER)->value('id'),
            'is_active' => true,
        ]);
    }

    public function test_a_deactivated_account_is_locked_out(): void
    {
        $this->manager->update(['is_active' => false]);

        $this->actingAs($this->manager)->get('/dashboard/inventory')->assertForbidden();
    }

    public function test_the_dashboard_needs_a_login(): void
    {
        $this->get('/dashboard/inventory')->assertRedirect('/login');
    }
}
