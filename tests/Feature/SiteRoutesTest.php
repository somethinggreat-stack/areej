<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'services' => ['/services'],
            'menu' => ['/menu'],
            'gallery' => ['/gallery'],
            'about' => ['/about'],
            'contact' => ['/contact'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_load(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_every_service_has_a_page(): void
    {
        foreach (config('catering_services') as $service) {
            $this->get('/services/'.$service['slug'])
                ->assertOk()
                ->assertSee($service['title']);
        }
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_can_be_indexed_by_search_engines(string $path): void
    {
        $this->get($path)
            ->assertDontSee('noindex', false)
            ->assertSee('<link rel="canonical" href="'.url($path).'">', false);
    }

    public function test_the_login_page_stays_out_of_search_results(): void
    {
        $this->get('/login')->assertSee('noindex', false);
    }

    public function test_the_sitemap_lists_every_public_page_and_service(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $paths = array_merge(
            array_column(self::publicRoutes(), 0),
            array_map(fn (array $service): string => '/services/'.$service['slug'], config('catering_services')),
        );

        foreach ($paths as $path) {
            $response->assertSee('<loc>'.url($path).'</loc>', false);
        }

        $this->assertStringStartsWith('<?xml', $response->getContent());
        $this->assertNotFalse(simplexml_load_string($response->getContent()), 'The sitemap is not valid XML.');
    }

    public function test_unknown_service_returns_404(): void
    {
        $this->get('/services/does-not-exist')->assertNotFound();
    }

    public function test_the_menu_page_shows_every_dish(): void
    {
        $response = $this->get('/menu');
        $dishCount = 0;

        foreach (config('menu') as $category) {
            foreach ($category['dishes'] as $dish) {
                $response->assertSee($dish['name']);
                $dishCount++;
            }
        }

        $this->assertSame(48, $dishCount, 'The menu board has 48 dishes.');
    }

    public function test_an_enquiry_is_stored(): void
    {
        $this->post('/contact', [
            'name' => 'Sara Khan',
            'phone' => '07700 900123',
            'event_type' => 'Weddings',
            'event_date' => now()->addMonth()->toDateString(),
            'guests' => 300,
        ])->assertRedirect(route('site.contact'));

        $enquiry = Enquiry::firstOrFail();

        $this->assertSame('Sara Khan', $enquiry->name);
        $this->assertSame(300, $enquiry->guests);
        $this->assertSame('new', $enquiry->status);
        $this->assertStringStartsWith('MC-', $enquiry->reference);
    }

    public function test_an_enquiry_needs_a_way_to_reply(): void
    {
        $this->post('/contact', ['name' => 'No Contact Details'])
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_guest_numbers_beyond_capacity_are_rejected(): void
    {
        $this->post('/contact', [
            'name' => 'Too Many',
            'phone' => '07700 900123',
            'guests' => 5000,
        ])->assertSessionHasErrors('guests');
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        $this->post('/contact', [
            'name' => 'Spam Bot',
            'phone' => '07700 900123',
            'company_website' => 'http://spam.example',
        ])->assertSessionHasErrors('company_website');

        $this->assertSame(0, Enquiry::count());
    }
}
