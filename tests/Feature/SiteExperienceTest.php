<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the scroll experiences, imagery and the multi-step quote form — the
 * parts a plain "does it return 200" check would happily miss.
 */
class SiteExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_carries_every_scroll_experience(): void
    {
        $response = $this->get('/');

        foreach ([
            'data-statement',        // word-by-word statement
            'data-story',            // pinned brand story
            'data-journey',          // horizontal services sequence
            'data-spotlight',        // menu spotlight
            'data-process',          // event process timeline
            'data-collage',          // scroll-driven collage
            'data-testimonials',     // review carousel
            'data-count-to',         // animated counters
        ] as $hook) {
            $response->assertSee($hook, false);
        }
    }

    public function test_the_chrome_is_present_on_every_page(): void
    {
        foreach (['/', '/services', '/menu', '/gallery', '/about', '/contact'] as $path) {
            $this->get($path)
                ->assertSee('data-cursor-ring', false)
                ->assertSee('data-transition', false)
                ->assertSee('data-nav-panel', false);
        }
    }

    public function test_an_unknown_url_gets_the_branded_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('This dish came')
            ->assertSee(config('midland.phone'));
    }

    public function test_the_gallery_shows_every_configured_image_once(): void
    {
        $files = array_column(config('story.gallery'), 'file');

        $this->assertSame($files, array_unique($files), 'The gallery lists the same photograph twice.');

        $response = $this->get('/gallery');
        $response->assertOk();

        foreach (config('story.gallery') as $item) {
            $response->assertSee($item['caption'], false);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function imageConfigs(): array
    {
        return [
            'gallery' => ['story.gallery'],
            'collage' => ['story.collage'],
        ];
    }

    #[DataProvider('imageConfigs')]
    public function test_configured_images_exist_on_disk(string $key): void
    {
        foreach (config($key) as $item) {
            $this->assertFileExists(
                public_path('img/'.$item['file'].'.jpg'),
                $item['file'].' is referenced in '.$key.' but not present in public/img.'
            );
        }
    }

    public function test_images_are_served_responsively(): void
    {
        $this->get('/')
            ->assertSee('<picture', false)
            ->assertSee('type="image/webp"', false)
            ->assertSee('img/r/karahi-naan-800.webp', false);
    }

    public function test_the_quote_form_has_all_four_steps(): void
    {
        $response = $this->get('/contact');

        foreach (['Occasion', 'Details', 'Service', 'You'] as $step) {
            $response->assertSee('data-quote-step="'.$step.'"', false);
        }

        $response->assertSee('data-quote-next', false)
            ->assertSee('data-quote-submit', false)
            ->assertSee('data-has-errors="false"', false);
    }

    public function test_a_rejected_enquiry_comes_back_with_every_step_open(): void
    {
        $response = $this->from('/contact')->post('/contact', [
            'name' => 'Test Person',
            // No phone and no email — the request should bounce.
        ]);

        $response->assertRedirect('/contact');

        $followUp = $this->followingRedirects()
            ->from('/contact')
            ->post('/contact', ['name' => 'Test Person']);

        $followUp->assertSee('data-has-errors="true"', false);

        // Every step must be reachable: the JS stands down on an error page, so
        // a `hidden` attribute here would strand fields the visitor must fix.
        foreach (['Details', 'Service', 'You'] as $step) {
            $followUp->assertDontSee('data-quote-step="'.$step.'" hidden', false);
        }
    }

    public function test_the_map_pins_the_verified_location(): void
    {
        $mc = config('midland');

        $this->get('/contact')
            ->assertSee($mc['place_id'], false)
            ->assertSee((string) $mc['geo']['lat'], false)
            ->assertSee((string) $mc['geo']['lng'], false);
    }

    /**
     * The client asked for no "scroll" prompts anywhere on the site.
     */
    public function test_no_page_prompts_the_visitor_to_scroll(): void
    {
        foreach (['/', '/services', '/menu', '/gallery', '/about', '/contact'] as $path) {
            $body = $this->get($path)->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/>\s*Scroll\s*(to explore)?\s*</i',
                $body,
                $path.' still shows a scroll prompt.'
            );
        }
    }

    /**
     * The client is not HMC certified and declined to publish prices.
     */
    public function test_no_page_claims_hmc_certification_or_shows_a_price(): void
    {
        foreach (['/', '/services', '/menu', '/gallery', '/about', '/contact'] as $path) {
            $body = $this->get($path)->getContent();

            $this->assertStringNotContainsStringIgnoringCase('HMC', $body, $path.' claims HMC certification.');
            $this->assertDoesNotMatchRegularExpression('/£\s?\d/', $body, $path.' shows a price.');
        }
    }
}
