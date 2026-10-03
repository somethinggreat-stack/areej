<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UK GDPR and cookie rules on the public site: the policies exist and are
 * linked, nothing from Google loads before consent, and an enquiry records
 * that the privacy notice was agreed to.
 */
class PrivacyAndCookiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_privacy_and_cookie_pages_load(): void
    {
        $this->get('/privacy')->assertOk()
            ->assertSee('Privacy notice')
            ->assertSee('09995085')
            ->assertSee('ico.org.uk', false);

        $this->get('/cookies')->assertOk()
            ->assertSee('Cookie policy')
            ->assertSee(config('session.cookie'))
            ->assertSee('mc_cookie_consent');
    }

    public function test_every_page_links_to_both_policies_and_offers_the_banner(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="'.route('site.privacy').'"', false)
            ->assertSee('href="'.route('site.cookies').'"', false)
            ->assertSee('data-cookie-banner', false)
            ->assertSee('data-cookie-settings', false);
    }

    public function test_the_google_map_does_not_load_before_consent(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('data-consent-map', false)
            ->assertSee('Show map')
            ->assertDontSee('<iframe', false);
    }

    public function test_an_enquiry_needs_the_privacy_tick_and_records_it(): void
    {
        $enquiry = ['name' => 'Sara Khan', 'phone' => '07700 900123'];

        $this->post('/contact', $enquiry)->assertSessionHasErrors('privacy_consent');
        $this->assertSame(0, Enquiry::count());

        $this->post('/contact', $enquiry + ['privacy_consent' => '1'])->assertSessionHasNoErrors();
        $this->assertNotNull(Enquiry::firstOrFail()->privacy_accepted_at);
    }

    public function test_both_policies_are_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(route('site.privacy'))
            ->assertSee(route('site.cookies'));
    }
}
