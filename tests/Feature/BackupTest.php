<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WagePayment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * The offline copy: one zip of Excel sheets, and putting missing records back
 * from it without touching what is already there.
 */
class BackupTest extends TestCase
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

    private function order(): Order
    {
        $order = Order::create([
            'reference' => 'ORD-AB123',
            'customer_name' => 'Latif Ahmed',
            'phone' => '07700 900123',
            'event_date' => '2026-09-22',
            'total_amount' => 25000,
            'status' => 'confirmed',
            'service_style' => 'not_set',
            'source' => 'notebook',
        ]);

        $order->items()->create(['description' => 'Chicken biryani', 'quantity' => 10, 'unit' => 'kg', 'unit_price' => 2500, 'position' => 1]);
        $order->payments()->create(['amount' => 10000, 'method' => 'cash', 'kind' => 'part_payment', 'paid_on' => '2026-09-23']);

        return $order;
    }

    /**
     * @return array<string, string>
     */
    private function unzip(TestResponse $response): array
    {
        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[$zip->getNameIndex($i)] = (string) $zip->getFromIndex($i);
        }
        $zip->close();

        return $files;
    }

    private function backupUpload(): UploadedFile
    {
        $response = $this->actingAs($this->manager)->get('/dashboard/backup/download')->assertOk();

        $copy = tempnam(sys_get_temp_dir(), 'test-backup').'.zip';
        copy($response->baseResponse->getFile()->getPathname(), $copy);

        return new UploadedFile($copy, 'midland-backup.zip', 'application/zip', null, true);
    }

    public function test_the_backup_zip_holds_every_sheet(): void
    {
        $this->order();

        $response = $this->actingAs($this->manager)->get('/dashboard/backup/download')->assertOk();
        $files = $this->unzip($response);

        foreach (['orders.csv', 'order-items.csv', 'payments.csv', 'customers.csv', 'staff.csv', 'shifts.csv', 'wages.csv', 'stock.csv', 'waste.csv', 'READ ME.txt'] as $name) {
            $this->assertArrayHasKey($name, $files);
        }

        $this->assertStringContainsString('ORD-AB123,2026-09-22,"Latif Ahmed"', $files['orders.csv']);
        $this->assertStringContainsString('250.00,100.00,150.00', $files['orders.csv']);
        $this->assertStringContainsString('"Chicken biryani",10,KG,25.00,250.00', $files['order-items.csv']);
        $this->assertStringContainsString('2026-09-23,100.00,Cash', $files['payments.csv']);
        $this->assertNotNull(Setting::get('last_backup_at'));

        $this->actingAs($this->manager)->get('/dashboard/backup')->assertOk()->assertSee('Last downloaded');
    }

    public function test_lost_orders_come_back_with_their_items_and_payments(): void
    {
        $this->order();
        $upload = $this->backupUpload();

        Order::withTrashed()->each(fn (Order $o) => $o->forceDelete());
        $this->assertSame(0, Order::count());

        $this->actingAs($this->manager)->post('/dashboard/backup/restore', ['file' => $upload])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $order = Order::where('reference', 'ORD-AB123')->firstOrFail();
        $this->assertSame('Latif Ahmed', $order->customer_name);
        $this->assertSame('2026-09-22', $order->event_date->toDateString());
        $this->assertSame(25000, $order->total_amount);
        $this->assertSame(10000, $order->paidAmount());
        $this->assertSame('kg', $order->items->first()->unit);
        $this->assertSame(25000, $order->items->first()->line_total);
    }

    public function test_restoring_twice_adds_nothing_the_second_time(): void
    {
        $order = $this->order();
        $upload = $this->backupUpload();

        $this->actingAs($this->manager)->post('/dashboard/backup/restore', ['file' => $upload]);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_a_payment_missing_from_an_existing_order_is_put_back(): void
    {
        $order = $this->order();
        $upload = $this->backupUpload();

        $order->payments()->delete();
        $order->update(['customer_name' => 'Latif A.']);

        $this->actingAs($this->manager)->post('/dashboard/backup/restore', ['file' => $upload]);

        $order->refresh();
        $this->assertSame(10000, $order->paidAmount());
        $this->assertSame('Latif A.', $order->customer_name, 'An order already on the website is not overwritten.');
    }

    public function test_wages_come_back_for_people_on_the_staff_page(): void
    {
        $person = StaffProfile::create(['full_name' => 'Imran Hussain', 'employment_type' => 'full_time', 'department' => 'kitchen', 'hourly_rate' => 12, 'is_active' => true]);
        WagePayment::create(['staff_profile_id' => $person->id, 'week_start' => '2026-09-21', 'amount' => 42000, 'paid_on' => '2026-09-28', 'method' => 'cash']);
        $upload = $this->backupUpload();

        WagePayment::query()->delete();

        $this->actingAs($this->manager)->post('/dashboard/backup/restore', ['file' => $upload]);

        $wage = WagePayment::firstOrFail();
        $this->assertSame(42000, $wage->amount);
        $this->assertSame('2026-09-28', $wage->paid_on->toDateString());
    }

    public function test_a_file_that_is_not_a_backup_is_refused(): void
    {
        $this->actingAs($this->manager)
            ->post('/dashboard/backup/restore', ['file' => UploadedFile::fake()->createWithContent('orders.csv', "Reference\nORD-1\n")])
            ->assertSessionHasErrors('file');
    }

    public function test_only_the_owner_and_management_can_back_up(): void
    {
        $finance = User::factory()->create(['role_id' => Role::where('slug', Role::FINANCE)->value('id'), 'is_active' => true]);

        $this->actingAs($finance)->get('/dashboard/backup')->assertForbidden();
        $this->actingAs($finance)->get('/dashboard/backup/download')->assertForbidden();
    }
}
