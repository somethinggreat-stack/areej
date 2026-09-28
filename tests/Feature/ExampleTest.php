<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path now serves the public marketing site; the dashboard lives
     * behind /dashboard and requires authentication.
     */
    public function test_the_homepage_is_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Midland Catering', false);
    }

    public function test_the_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
