<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Menu;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Only the sections the business uses are on the menu; the owner chooses
 * which in Settings, and nothing hidden is lost.
 */
class MenuSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, SettingSeeder::class]);

        $this->owner = User::factory()->create([
            'role_id' => Role::where('slug', Role::OWNER)->value('id'),
            'is_active' => true,
        ]);
    }

    /**
     * The sidebar alone, so a link elsewhere on the page does not count.
     */
    private function menu(): string
    {
        $page = $this->actingAs($this->owner)->get('/dashboard')->assertOk()->getContent();

        preg_match('#<aside.*?</aside>#s', $page, $match);

        return $match[0] ?? '';
    }

    /**
     * Every other setting as it is, plus the menu boxes ticked.
     *
     * @param  list<string>  $shown
     * @return array<string, mixed>
     */
    private function settingsWithMenu(array $shown): array
    {
        $form = [];
        foreach (Setting::all() as $setting) {
            $form[$setting->key] = $setting->castValue();
        }

        return ['menu_present' => 1, 'menu_shown' => $shown] + $form;
    }

    public function test_the_menu_starts_with_only_the_simple_sections(): void
    {
        $menu = $this->menu();

        foreach (['orders', 'customers', 'payments', 'weekly-summary', 'waste', 'staff', 'timesheets', 'backup', 'settings'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $menu, "$route should be on the menu");
        }

        foreach (['calendar', 'prep', 'stock-counts', 'purchase-orders', 'expenses', 'reports', 'activity'] as $route) {
            $this->assertStringNotContainsString('href="'.route($route).'"', $menu, "$route should be off the menu");
        }
    }

    public function test_the_owner_chooses_the_sections_in_settings(): void
    {
        $this->actingAs($this->owner)
            ->patch('/dashboard/settings', $this->settingsWithMenu(['orders', 'reports']))
            ->assertSessionHasNoErrors();

        $menu = $this->menu();
        $this->assertStringContainsString('href="'.route('reports').'"', $menu);
        $this->assertStringNotContainsString('href="'.route('waste').'"', $menu);
        $this->assertStringContainsString('href="'.route('settings').'"', $menu, 'Settings can never be hidden.');

        $this->assertContains('waste', Menu::hidden());
        $this->assertNotContains('reports', Menu::hidden());
        $this->actingAs($this->owner)->get('/dashboard/settings')->assertOk()->assertSee('Tick the sections the business uses');
    }

    public function test_a_hidden_section_still_opens(): void
    {
        $this->actingAs($this->owner)->get('/dashboard/reports')->assertOk();
    }

    public function test_saving_other_settings_leaves_the_menu_alone(): void
    {
        $this->actingAs($this->owner)
            ->patch('/dashboard/settings', ['deposit_percent' => 30] + array_diff_key($this->settingsWithMenu([]), ['menu_present' => 1, 'menu_shown' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Setting::get('menu_hidden'));
    }
}
