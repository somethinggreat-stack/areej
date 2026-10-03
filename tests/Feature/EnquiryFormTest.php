<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client asked for the quote enquiry to be a single, simple page.
 */
class EnquiryFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_shows_one_form_with_a_send_enquiry_button(): void
    {
        $response = $this->get('/contact')->assertOk();

        $body = $response->getContent();

        $this->assertSame(1, substr_count($body, 'data-enquiry-form'));
        $this->assertSame(1, substr_count($body, 'type="submit"'));

        $response->assertSee('Send enquiry')
            ->assertSee('How can we reach you?')
            ->assertSee('either is enough', false)
            ->assertDontSee('data-quote-step', false)
            ->assertDontSee('data-quote-next', false)
            ->assertDontSee('Continue');
    }

    public function test_every_field_is_on_the_page_with_contact_details_first(): void
    {
        $body = $this->get('/contact')->getContent();

        $fields = ['name', 'phone', 'email', 'event_type', 'event_date', 'guests', 'venue', 'service_style', 'extras[]', 'dietary', 'message'];

        $positions = array_map(fn (string $field): int|false => strpos($body, 'name="'.$field.'"'), $fields);

        $this->assertNotContains(false, $positions, 'Every enquiry field must be rendered.');
        $this->assertSame($positions, collect($positions)->sort()->values()->all(), 'Fields should appear in the natural order.');
    }

    public function test_a_full_enquiry_from_the_single_page_is_stored(): void
    {
        $this->post('/contact', [
            'name' => 'Amina Begum',
            'privacy_consent' => '1',
            'phone' => '07700 900456',
            'email' => 'amina@example.com',
            'event_type' => 'Weddings',
            'event_date' => now()->addMonths(2)->toDateString(),
            'guests' => 250,
            'venue' => 'Community hall, Sparkbrook',
            'service_style' => 'Delivered and served',
            'extras' => ['Waiter service', 'Serving dishes'],
            'dietary' => '20 vegetarian',
            'message' => 'Evening reception.',
            'company_website' => '',
        ])->assertRedirect(route('site.contact'))
            ->assertSessionHas('enquiry_reference');

        $enquiry = Enquiry::sole();

        $this->assertSame('Amina Begum', $enquiry->name);
        $this->assertSame(250, $enquiry->guests);
        $this->assertSame('Delivered and served', $enquiry->service_style);
        $this->assertSame(['Waiter service', 'Serving dishes'], $enquiry->extras);
    }
}
