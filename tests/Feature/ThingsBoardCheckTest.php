<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * thingsboard:check is the only way to confirm, from the server itself, that
 * the partner's ThingsBoard instance is reachable and what it exposes — the
 * device/key names it prints are what the parameter mapping is built from.
 */
class ThingsBoardCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'thingsboard.url'      => 'http://tb.test',
            'thingsboard.username' => 'user@example.com',
            'thingsboard.password' => 'secret',
        ]);
    }

    private function fakeThingsBoard(array $user): void
    {
        Http::fake([
            'tb.test/api/auth/login' => Http::response(['token' => 'jwt-token', 'refreshToken' => 'r']),
            'tb.test/api/auth/user'  => Http::response($user),
            'tb.test/api/*/devices*' => Http::sequence()
                ->push(['data' => [['id' => ['id' => 'dev-1'], 'name' => 'Station A', 'type' => 'weather']], 'hasNext' => true])
                ->push(['data' => [['id' => ['id' => 'dev-2'], 'name' => 'Station B', 'type' => 'weather']], 'hasNext' => false]),
            'tb.test/api/plugins/telemetry/DEVICE/dev-1/values/timeseries*' => Http::response([
                'temperature' => [['ts' => 1759830000000, 'value' => '31.4']],
            ]),
            'tb.test/api/plugins/telemetry/DEVICE/dev-2/values/timeseries*' => Http::response([]),
        ]);
    }

    public function test_lists_every_page_of_devices_with_their_latest_telemetry(): void
    {
        $this->fakeThingsBoard(['email' => 'user@example.com', 'authority' => 'TENANT_ADMIN']);

        $this->artisan('thingsboard:check')
            ->expectsOutputToContain('Connected to http://tb.test as user@example.com (TENANT_ADMIN).')
            ->expectsOutputToContain('2 device(s) found.')
            ->expectsOutputToContain('Station A')
            ->expectsOutputToContain('temperature')
            ->expectsOutputToContain('Station B')
            ->expectsOutputToContain('No telemetry.')
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/tenant/devices')
            && $r->hasHeader('X-Authorization', 'Bearer jwt-token'));
        Http::assertSentCount(7); // one login, two user lookups, two device pages, two telemetry reads
    }

    public function test_customer_users_list_devices_through_the_customer_endpoint(): void
    {
        $this->fakeThingsBoard([
            'email' => 'user@example.com', 'authority' => 'CUSTOMER_USER', 'customerId' => ['id' => 'cust-9'],
        ]);

        $this->artisan('thingsboard:check')->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/customer/cust-9/devices'));
    }

    public function test_device_option_narrows_the_output_to_one_device(): void
    {
        $this->fakeThingsBoard(['email' => 'user@example.com', 'authority' => 'TENANT_ADMIN']);

        $this->artisan('thingsboard:check', ['--device' => 'Station A'])
            ->expectsOutputToContain('1 device(s) found.')
            ->doesntExpectOutputToContain('Station B')
            ->assertSuccessful();
    }

    public function test_fails_cleanly_when_credentials_are_rejected(): void
    {
        Http::fake(['tb.test/api/auth/login' => Http::response(['message' => 'Invalid username or password'], 401)]);

        $this->artisan('thingsboard:check')->assertFailed();
    }

    public function test_fails_cleanly_when_not_configured(): void
    {
        config(['thingsboard.password' => null]);
        Http::fake();

        $this->artisan('thingsboard:check')
            ->expectsOutputToContain('ThingsBoard is not configured')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
