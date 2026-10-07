<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * thingsboard:setup is what gets run, once, on an installation we don't
 * operate ourselves: it has to land on the sites that are already there
 * instead of duplicating them, and be harmless to run a second time.
 */
class ThingsBoardSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-07 15:00:00');

        config([
            'thingsboard.url'      => 'http://tb.test',
            'thingsboard.username' => 'user@example.com',
            'thingsboard.password' => 'secret',
        ]);

        $point = [['ts' => Carbon::parse('2026-10-07 14:40:00')->getTimestampMs(), 'value' => 'true']];

        Http::fake([
            'tb.test/api/auth/login'      => Http::response(['token' => 'jwt-token']),
            'tb.test/api/auth/user'       => Http::response(['authority' => 'TENANT_ADMIN']),
            'tb.test/api/tenant/devices*' => Http::response(['hasNext' => false, 'data' => [
                ['id' => ['id' => 'a'], 'name' => 'UTG-APV-Valve-1'],
                ['id' => ['id' => 'b'], 'name' => 'AfriFarm-Reference-Valve-2'],
                ['id' => ['id' => 'c'], 'name' => 'IPR-APV-Valve-1'],
            ]]),
            'tb.test/api/plugins/telemetry/*' => Http::response(['valve_state' => $point]),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_creates_the_configured_sites_with_their_parameters_on_an_empty_installation(): void
    {
        $admin = $this->admin();

        $this->artisan('thingsboard:setup')
            ->expectsOutputToContain('UTG: creating site "University of The Gambia" — 1 device(s).')
            ->expectsOutputToContain('AfriFarm: creating site "AfriFarm" — 1 device(s).')
            ->expectsOutputToContain('schedule:run')
            ->assertSuccessful();

        $this->assertSame(['UTG', 'AfriFarm'], Site::orderBy('id')->pluck('thingsboard_prefix')->all());

        $utg = Site::where('thingsboard_prefix', 'UTG')->first();
        $this->assertSame('active', $utg->status);
        $this->assertSame($admin->id, $utg->user_id);
        $this->assertSame('Gambia', $utg->country);
        $this->assertNotNull($utg->thingsboard_synced_at);

        $this->assertSame(
            ['apv_valve_1_state', 'reference_valve_2_state'],
            SiteParameter::orderBy('id')->pluck('slug')->all(),
        );
        $this->assertSame(2, SensorReading::count());
    }

    public function test_links_a_site_that_already_exists_under_a_longer_name_instead_of_duplicating_it(): void
    {
        $this->admin();
        $existing = Site::factory()->create(['name' => 'UTG Agrivoltaic Site - GAM', 'status' => 'active']);
        $category = SiteCategory::factory()->create([
            'site_id' => $existing->id, 'slug' => 'irrigation', 'is_active' => true, 'offline_threshold_minutes' => 5,
        ]);

        $this->artisan('thingsboard:setup')
            ->expectsOutputToContain('UTG: linking existing site "UTG Agrivoltaic Site - GAM"')
            ->assertSuccessful();

        $this->assertSame('UTG', $existing->refresh()->thingsboard_prefix);
        $this->assertSame(1, Site::where('name', 'like', '%UTG%')->orWhere('name', 'University of The Gambia')->count());
        $this->assertSame(60, $category->refresh()->offline_threshold_minutes);
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->admin();

        $this->artisan('thingsboard:setup')->assertSuccessful();
        $this->artisan('thingsboard:setup')
            ->expectsOutputToContain('UTG: already linked to site "University of The Gambia"')
            ->assertSuccessful();

        $this->assertSame(2, Site::count());
        $this->assertSame(2, SiteParameter::count());
        $this->assertSame(2, SensorReading::count());
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->admin();
        $existing = Site::factory()->create(['name' => 'AfriFarm Agrivoltaic Site']);

        $this->artisan('thingsboard:setup', ['--dry-run' => true])
            ->expectsOutputToContain('UTG: creating site "University of The Gambia"')
            ->expectsOutputToContain('AfriFarm: linking existing site "AfriFarm Agrivoltaic Site"')
            ->expectsOutputToContain('nothing was changed')
            ->assertSuccessful();

        $this->assertSame(1, Site::count());
        $this->assertNull($existing->refresh()->thingsboard_prefix);
        $this->assertSame(0, SiteParameter::count());
    }

    public function test_stops_rather_than_guess_between_two_candidate_sites(): void
    {
        $this->admin();
        Site::factory()->create(['name' => 'UTG Agrivoltaic Site - GAM']);
        Site::factory()->create(['name' => 'UTG test']);

        $this->artisan('thingsboard:setup')
            ->expectsOutputToContain('UTG: several sites could match')
            ->assertFailed();

        $this->assertSame(0, Site::whereNotNull('thingsboard_prefix')->count());
    }

    public function test_needs_an_owner_to_create_a_site(): void
    {
        $this->artisan('thingsboard:setup')
            ->expectsOutputToContain('no owner was found')
            ->assertFailed();

        $agent = User::factory()->create(['role' => 'agent', 'status' => 'active']);

        $this->artisan('thingsboard:setup', ['--owner' => $agent->email])->assertSuccessful();

        $this->assertSame([$agent->id], Site::pluck('user_id')->unique()->all());
    }
}
