<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_page_asked_over_http_is_sent_to_https_when_the_site_address_is_https(): void
    {
        config(['app.url' => 'https://dashboard.example.test']);

        $this->get('http://dashboard.example.test/login?next=1')
            ->assertStatus(301)
            ->assertRedirect('https://dashboard.example.test/login?next=1');

        $this->get('https://dashboard.example.test/login')->assertOk();
    }

    public function test_an_installation_without_https_is_left_alone(): void
    {
        config(['app.url' => 'http://dashboard.example.test']);

        $this->get('http://dashboard.example.test/login')->assertOk();
    }

    public function test_a_device_posting_over_http_is_not_redirected(): void
    {
        config(['app.url' => 'https://dashboard.example.test']);

        // Reaches the API (which answers that the site is unknown) instead of
        // a 301 that the device would replay as a GET, losing its readings.
        $this->postJson('http://dashboard.example.test/api/sensors/some-site/solar', ['solar_output' => 1])
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
