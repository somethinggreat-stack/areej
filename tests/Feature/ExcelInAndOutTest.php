<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExcelInAndOutTest extends TestCase
{
    use RefreshDatabase;

    private const BOM = "\xEF\xBB\xBF";

    private User $manager;

    private InventoryCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->manager = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGEMENT)->value('id'),
            'is_active' => true,
        ]);

        $this->category = InventoryCategory::create([
            'name_en' => 'Rice & Grains',
            'slug' => 'rice-grains',
            'kind' => 'food',
            'sort_order' => 1,
        ]);
    }

    private function staff(array $overrides = []): StaffProfile
    {
        return StaffProfile::create(array_merge([
            'full_name' => 'Imran Hussain',
            'phone' => '07700 900123',
            'employment_type' => 'full_time',
            'department' => 'kitchen',
            'hourly_rate' => 12.00,
            'overtime_rate' => 18.00,
            'holiday_allowance_hours' => 224,
            'started_on' => '2025-04-01',
            'is_active' => true,
        ], $overrides));
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'inventory_category_id' => $this->category->id,
            'name_en' => 'Basmati Rice',
            'name_ur' => 'باسمتی چاول',
            'unit' => 'kg',
            'current_quantity' => 100,
            'reorder_level' => 40,
            'unit_cost' => 2.00,
            'count_frequency' => 'weekly',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function csv(array $rows): UploadedFile
    {
        $handle = fopen('php://temp', 'w+b');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = self::BOM.stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('sheet.csv', $contents);
    }

    public function test_staff_export_is_an_excel_ready_csv(): void
    {
        $this->staff();

        $response = $this->actingAs($this->manager)->get(route('excel.staff.export'));

        $response->assertOk();
        $this->assertStringContainsString('midland-staff-'.now()->format('Y-m-d').'.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith(self::BOM.'"Full name",Phone,"Employment type",Department', $body);
        $this->assertStringContainsString('"Imran Hussain","07700 900123","Full time",Kitchen,12.00,18.00,224,2025-04-01,Yes', $body);
    }

    public function test_shift_export_covers_the_requested_dates(): void
    {
        AttendanceRecord::create([
            'staff_profile_id' => $this->staff()->id,
            'worked_on' => '2026-09-28',
            'clock_in_at' => '2026-09-28 09:00',
            'clock_out_at' => '2026-09-28 17:30',
            'break_minutes' => 30,
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('excel.shifts.export', ['from' => '2026-09-28', 'to' => '2026-10-04']));

        $response->assertOk();
        $this->assertStringContainsString('midland-shifts-2026-09-28-to-2026-10-04.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith(self::BOM.'Date,Name,Job,"Rostered start","Clock in","Clock out","Break minutes","Paid hours",Status', $body);
        $this->assertStringContainsString('2026-09-28,"Imran Hussain",Kitchen,,09:00,17:30,30,8.00,Worked', $body);
    }

    public function test_stock_export_keeps_urdu_names(): void
    {
        $this->item();

        $response = $this->actingAs($this->manager)->get(route('excel.stock.export'));

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringStartsWith(self::BOM.'"Name (English)","Name (Urdu)",Category,Unit,"On hand"', $body);
        $this->assertStringContainsString('"Basmati Rice","باسمتی چاول","Rice & Grains",kg,100,40,,2.00,,,Weekly,Yes', $body);
    }

    public function test_templates_are_the_header_row_only(): void
    {
        $body = $this->actingAs($this->manager)->get(route('excel.stock.template'))->streamedContent();

        $this->assertSame(1, substr_count(trim($body), "\n") + 1);
        $this->assertStringStartsWith(self::BOM.'"Name (English)"', $body);
    }

    public function test_the_pages_show_the_excel_tools_to_management(): void
    {
        $this->actingAs($this->manager)->get(route('staff'))
            ->assertOk()->assertSee(route('excel.staff.export'))->assertSee('Import from Excel');
        $this->actingAs($this->manager)->get(route('inventory'))
            ->assertOk()->assertSee(route('excel.stock.template'))
            ->assertSee('Quantities are only set for new items');
        $this->actingAs($this->manager)->get(route('attendance'))
            ->assertOk()->assertSee(route('excel.shifts.export'));
        $this->actingAs($this->manager)->get(route('orders'))
            ->assertOk()->assertSee(route('reports.export', 'jobs'));
    }

    public function test_staff_round_trip_updates_existing_people_and_adds_new_ones(): void
    {
        $this->staff();

        $exported = $this->actingAs($this->manager)->get(route('excel.staff.export'))->streamedContent();

        // What someone does in Excel: change a rate, add a person underneath.
        // Excel also rewrites the date day-first when it saves.
        $edited = str_replace(['12.00', '2025-04-01'], ['13.50', '01/04/2025'], $exported)
            .'"ayesha khan",7700900456,"Event staff",Service,11,,,,'."\n";

        $this->actingAs($this->manager)
            ->post(route('excel.staff.import'), ['file' => UploadedFile::fake()->createWithContent('staff.csv', $edited)])
            ->assertRedirect()
            ->assertSessionHas('import_skipped', [])->assertSessionHas('status', '1 added, 1 updated, 0 skipped');

        $imran = StaffProfile::where('full_name', 'Imran Hussain')->sole();
        $this->assertSame('13.50', $imran->hourly_rate);
        $this->assertSame('2025-04-01', $imran->started_on->toDateString());
        $this->assertSame('07700 900123', $imran->phone);

        $ayesha = StaffProfile::where('full_name', 'ayesha khan')->sole();
        $this->assertSame('event_staff', $ayesha->employment_type);
        $this->assertSame('service', $ayesha->department);
        $this->assertSame('07700900456', $ayesha->phone);
        $this->assertTrue($ayesha->is_active);

        $this->assertSame(2, StaffProfile::count());
        $this->assertTrue(ActivityLog::where('action', 'imported')->exists());
    }

    public function test_staff_import_needs_only_a_name_and_matches_it_in_any_case(): void
    {
        $this->staff();

        $this->actingAs($this->manager)
            ->post(route('excel.staff.import'), ['file' => $this->csv([
                ['FULL NAME', 'phone'],
                ['imran hussain', '0121 555 0000'],
                ['Zara Malik', ''],
                ['', '0121 000 0000'],
                ['Omar Farooq', ''],
            ])])
            ->assertSessionHas('status', '2 added, 1 updated, 1 skipped')
            ->assertSessionHas('import_skipped', ['Row 4: Full name is empty.']);

        $imran = StaffProfile::where('full_name', 'imran hussain')->sole();
        $this->assertSame('0121 555 0000', $imran->phone);
        $this->assertSame('12.00', $imran->hourly_rate);

        $zara = StaffProfile::where('full_name', 'Zara Malik')->sole();
        $this->assertSame('full_time', $zara->employment_type);
        $this->assertSame('kitchen', $zara->department);
        $this->assertTrue($zara->is_active);
    }

    public function test_staff_rows_that_cannot_be_read_are_skipped_with_a_reason(): void
    {
        $this->actingAs($this->manager)
            ->post(route('excel.staff.import'), ['file' => $this->csv([
                ['Full name', 'Employment type', 'Hourly rate'],
                ['Zara Malik', 'Contractor', ''],
                ['Omar Farooq', '', 'lots'],
            ])])
            ->assertSessionHas('status', '0 added, 0 updated, 2 skipped');

        $skipped = session('import_skipped');
        $this->assertStringStartsWith('Row 2: Employment type "Contractor" is not recognised', $skipped[0]);
        $this->assertStringStartsWith('Row 3: Hourly rate "lots" is not a number', $skipped[1]);
        $this->assertSame(0, StaffProfile::count());
    }

    public function test_a_sheet_without_the_heading_row_is_refused(): void
    {
        $this->actingAs($this->manager)
            ->post(route('excel.staff.import'), ['file' => $this->csv([['Imran Hussain', '07700 900123']])])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, StaffProfile::count());
    }

    public function test_stock_import_opens_new_items_through_the_ledger_and_leaves_existing_quantities_alone(): void
    {
        $rice = $this->item();

        $this->actingAs($this->manager)
            ->post(route('excel.stock.import'), ['file' => $this->csv([
                ['Name (English)', 'Name (Urdu)', 'Category', 'Unit', 'On hand', 'Reorder at', 'Usual order', 'Cost per unit', 'Supplier', 'Code', 'Count how often', 'Active'],
                ['basmati rice', 'باسمتی چاول', 'Rice & Grains', 'kg', '5', '50', '100', '£2.25', '', '', 'Weekly', 'Yes'],
                ['Chickpeas', 'چنے', 'rice & grains', 'kg', '25', '10', '', '1.10', '', 'CHK-1', 'Before every job', 'Yes'],
                ['Paper plates', '', 'Disposables', 'pack', '12', '5', '', '', '', '', 'Weekly', 'Yes'],
            ])])
            ->assertSessionHas('status', '1 added, 1 updated, 1 skipped');

        $rice->refresh();
        $this->assertSame('100.000', $rice->current_quantity);
        $this->assertSame('50.000', $rice->reorder_level);
        $this->assertSame('2.25', $rice->unit_cost);
        $this->assertSame(0, $rice->movements()->count());

        $chickpeas = InventoryItem::where('name_en', 'Chickpeas')->sole();
        $this->assertSame('25.000', $chickpeas->current_quantity);
        $this->assertSame('چنے', $chickpeas->name_ur);
        $this->assertSame('per_event', $chickpeas->count_frequency);
        $this->assertSame('CHK-1', $chickpeas->sku);

        $opening = $chickpeas->movements()->sole();
        $this->assertSame(StockMovement::TYPE_OPENING, $opening->type);
        $this->assertSame('25.000', $opening->quantity_change);
        $this->assertSame($this->manager->id, $opening->recorded_by);

        $this->assertFalse(InventoryItem::where('name_en', 'Paper plates')->exists());
        $this->assertStringStartsWith('Row 4: Category "Disposables" does not exist', session('import_skipped')[0]);
    }

    public function test_the_import_rejects_files_that_are_not_csv(): void
    {
        $this->actingAs($this->manager)
            ->post(route('excel.stock.import'), ['file' => UploadedFile::fake()->create('stock.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
    }

    public function test_below_management_cannot_export_or_import(): void
    {
        $kitchen = User::factory()->create([
            'role_id' => Role::where('slug', Role::MANAGER)->value('id'),
            'is_active' => true,
        ]);

        foreach (['excel.staff.export', 'excel.shifts.export', 'excel.stock.export', 'excel.staff.template', 'excel.stock.template'] as $route) {
            $this->actingAs($kitchen)->get(route($route))->assertForbidden();
        }

        $file = $this->csv([['Full name'], ['Zara Malik']]);

        $this->actingAs($kitchen)->post(route('excel.staff.import'), ['file' => $file])->assertForbidden();
        $this->actingAs($kitchen)->post(route('excel.stock.import'), ['file' => $file])->assertForbidden();
        $this->assertSame(0, StaffProfile::count());

        // The kitchen manager still uses the inventory page, just without the Excel tools.
        $this->actingAs($kitchen)->get(route('inventory'))->assertOk()->assertDontSee('Import from Excel');
    }
}
