<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintedMenusTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_menu_book_lists_every_dish_without_a_packages_section(): void
    {
        $response = $this->get('/menu/book')->assertOk();

        foreach (config('menu') as $section) {
            foreach ($section['dishes'] as $dish) {
                $response->assertSee($dish['name']);
            }
        }

        $response->assertDontSee('Package No.');
    }

    public function test_each_package_menu_shows_three_numbered_packages(): void
    {
        foreach (config('packages') as $packageMenu) {
            $this->assertCount(3, $packageMenu['packages']);

            $this->get('/packages/'.$packageMenu['slug'])
                ->assertOk()
                ->assertSee($packageMenu['title'])
                ->assertSeeInOrder(['Package No. 1', 'Package No. 2', 'Package No. 3']);
        }
    }

    public function test_an_unknown_package_menu_is_not_found(): void
    {
        $this->get('/packages/no-such-menu')->assertNotFound();
    }

    public function test_printed_menus_stay_out_of_search_until_signed_off(): void
    {
        $this->get('/menu/book')->assertSee('noindex', false);
        $this->get('/packages/daily')->assertSee('noindex', false);
    }

    public function test_package_dishes_all_come_from_the_menu(): void
    {
        $menuDishes = collect(config('menu'))->pluck('dishes')->flatten(1)->pluck('name');

        foreach (config('packages') as $packageMenu) {
            foreach ($packageMenu['packages'] as $package) {
                foreach (collect($package['courses'])->flatten() as $dish) {
                    $this->assertContains($dish, $menuDishes, "{$dish} in {$packageMenu['title']} is not on the menu.");
                }
            }
        }
    }

    public function test_the_menu_carries_the_clients_corrections(): void
    {
        $this->get('/menu')
            ->assertOk()
            ->assertSee('Chicken Malai Boti')
            ->assertSee('Cream marinade, mild and tender')
            ->assertDontSee('cheese marinade')
            ->assertSee('Chicken Sheesh Kebab');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('For inquiries, call')
            ->assertDontSee('>The kitchen<', false);
    }
}
