<?php

namespace Tests\Feature\Admin;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Facts about one site (its tank, where its sensors stand, which source feeds
 * it) are data an admin manages, not constants shared by every site. These
 * tests cover the admin paths that replaced the hardcoded versions.
 */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-13 12:00:00');
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);

        config([
            'thingsboard.url' => 'http://tb.test', 'thingsboard.username' => 'u', 'thingsboard.password' => 'p',
            'licor.url' => 'http://licor.test/v2', 'licor.token' => 'secret',
        ]);
    }

    private function siteForm(Site $site, array $overrides = []): array
    {
        return $overrides + [
            'name' => $site->name, 'country' => $site->country, 'user_id' => $site->user_id, 'status' => 'active',
        ];
    }

    private function fakeThingsBoard(array $devices, array $telemetry = []): void
    {
        Http::fake([
            'tb.test/api/auth/login'          => Http::response(['token' => 't']),
            'tb.test/api/auth/user'           => Http::response(['authority' => 'TENANT_ADMIN']),
            'tb.test/api/tenant/devices*'     => Http::response(['hasNext' => false, 'data' => array_map(
                fn ($name) => ['id' => ['id' => $name], 'name' => $name], $devices)]),
            'tb.test/api/plugins/telemetry/*' => Http::response($telemetry),
            'licor.test/v2/devices*'          => Http::response(['devices' => [
                ['deviceSerialNumber' => '22225708', 'deviceName' => 'Mali - 22225708', 'sensors' => [[], []]],
            ]]),
        ]);
    }

    public function test_tank_volume_uses_the_sites_own_tank_and_is_not_computed_without_one(): void
    {
        $point = ['tank_level' => [['ts' => Carbon::parse('2026-10-13 11:40:00')->getTimestampMs(), 'value' => '100']]];
        $this->fakeThingsBoard(['Big-General-Pressure-0', 'Unknown-General-Pressure-0'], $point);

        // Two tanks of radius 100 cm: π × 100² × 100 cm / 1000 × 2 = 6283 L.
        $big = Site::factory()->create(['status' => 'active', 'thingsboard_prefix' => 'Big', 'tank_radius_cm' => 100, 'tank_count' => 2]);
        $unknown = Site::factory()->create(['status' => 'active', 'thingsboard_prefix' => 'Unknown']);

        $this->artisan('thingsboard:sync')->assertSuccessful();

        $volume = fn (Site $site) => SiteParameter::where('metric', 'tank_volume')
            ->whereHas('category', fn ($q) => $q->where('site_id', $site->id))->first();

        $this->assertSame(6283.0, $volume($big)->latestReading->value);
        $this->assertNull($volume($unknown));
        // The measured height itself is still stored for the site without a tank geometry.
        $this->assertSame(1, SiteParameter::where('metric', 'tank_level')
            ->whereHas('category', fn ($q) => $q->where('site_id', $unknown->id))->count());
    }

    public function test_editing_sensor_locations_renames_the_sites_licor_parameters(): void
    {
        $site = Site::factory()->create(['status' => 'active']);
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $make = fn (string $slug, string $name, string $metric) => SiteParameter::factory()->create(
            ['site_category_id' => $category->id, 'slug' => $slug, 'name' => $name, 'metric' => $metric]);

        $par    = $make('licor_22261662_1', 'PAR – 22261662', 'par');
        $custom = $make('licor_22261663_1', 'My own name', 'par');
        $temp   = $make('licor_22034652_1', 'Air temperature – 22034652', 'air_temperature');

        $this->actingAs($this->admin)->put(route('admin.sites.update', $site), $this->siteForm($site, [
            'licor_locations' => "22261662 = Reference\n22261663: Under PV\n\n22034652 = Reference station\nnot a valid line",
            'tank_radius_cm'  => 180,
            'tank_count'      => 2,
        ]))->assertRedirect();

        $site->refresh();
        $this->assertSame(['22261662' => 'Reference', '22261663' => 'Under PV', '22034652' => 'Reference station'], $site->licor_locations);
        $this->assertEqualsWithDelta(203.58, $site->tankLitresPerCm(), 0.01);

        $this->assertSame(['Reference – PAR', 'par_reference'], [$par->refresh()->name, $par->metric]);
        // A hand-picked name is kept; what it measures still follows the location.
        $this->assertSame(['My own name', 'par_under_pv'], [$custom->refresh()->name, $custom->metric]);
        $this->assertSame(['Reference station – Air temperature', 'air_temperature'], [$temp->refresh()->name, $temp->metric]);

        // Changing a location again renames again; clearing it goes back to the serial.
        $this->put(route('admin.sites.update', $site), $this->siteForm($site, ['licor_locations' => '22261662 = Open field']));
        $this->assertSame('Open field – PAR', $par->refresh()->name);
        $this->assertSame('par_open_field', $par->metric);
        $this->assertSame('Air temperature – 22034652', $temp->refresh()->name);
    }

    public function test_data_sources_page_lists_what_the_accounts_expose_and_links_them(): void
    {
        $this->fakeThingsBoard(['IPR-APV-Valve-1', 'IPR-Reference-Humidity-2', 'UTG-APV-Valve-1', 'ZZ_TEST']);
        $utg  = Site::factory()->create(['name' => 'University of The Gambia', 'status' => 'active', 'thingsboard_prefix' => 'UTG']);
        $mali = Site::factory()->create(['name' => 'Mali', 'status' => 'active']);

        $this->actingAs($this->admin)->get(route('admin.sources'))->assertOk()
            ->assertSee('IPR')->assertSee('2 mapped device(s)')
            ->assertSee('Mali - 22225708')->assertSee('2 sensor(s)')
            ->assertDontSee('ZZ_TEST');

        // Link an existing site to a ThingsBoard prefix...
        $this->post(route('admin.sources.link'), ['source' => 'thingsboard', 'key' => 'IPR', 'site_id' => $mali->id])
            ->assertSessionHas('success');
        $this->assertSame('IPR', $mali->refresh()->thingsboard_prefix);

        // ...create a site for a LI-COR station...
        $this->post(route('admin.sources.link'), ['source' => 'licor', 'key' => '22225708', 'name' => 'IPR Katibougou', 'country' => 'Mali'])
            ->assertSessionHas('success');
        $created = Site::where('licor_device_serial', '22225708')->firstOrFail();
        $this->assertSame(['IPR Katibougou', 'Mali', 'active', $this->admin->id], [$created->name, $created->country, $created->status, $created->user_id]);

        // ...but never give one source to two sites, nor two sources of a kind to one site.
        $this->post(route('admin.sources.link'), ['source' => 'thingsboard', 'key' => 'UTG', 'site_id' => $created->id])
            ->assertSessionHas('error');
        $this->post(route('admin.sources.link'), ['source' => 'thingsboard', 'key' => 'AfriFarm', 'site_id' => $utg->id])
            ->assertSessionHas('error');
        $this->assertSame('UTG', $utg->refresh()->thingsboard_prefix);
        $this->assertNull($created->refresh()->thingsboard_prefix);
    }

    public function test_data_sources_are_admin_only_and_survive_an_unreachable_account(): void
    {
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active']);
        $this->actingAs($agent)->get(route('admin.sources'))->assertForbidden();

        config(['licor.token' => null]);
        Http::fake(['tb.test/*' => Http::response(['message' => 'Invalid credentials'], 401)]);

        $this->actingAs($this->admin)->get(route('admin.sources'))->assertOk()
            ->assertSee('Could not list the ThingsBoard sources')
            ->assertSee('LI-COR is not configured');
    }

    public function test_what_a_parameter_measures_can_be_set_by_the_admin_and_is_not_lost_by_other_forms(): void
    {
        $site = Site::factory()->create(['status' => 'active']);
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'weather']);
        $param = SiteParameter::factory()->create(['site_category_id' => $category->id, 'name' => 'Outside temp', 'data_type' => 'float']);
        $url = route('admin.parameters.update', [$site, $category, $param]);

        $this->actingAs($this->admin)->put($url, ['name' => 'Outside temp', 'data_type' => 'float', 'metric' => 'air_temperature']);
        $this->assertSame('air_temperature', $param->refresh()->metric);

        // A request that doesn't carry the field leaves it alone...
        $this->put($url, ['name' => 'Outside temp', 'data_type' => 'float']);
        $this->assertSame('air_temperature', $param->refresh()->metric);

        // ...an explicit empty choice clears it.
        $this->put($url, ['name' => 'Outside temp', 'data_type' => 'float', 'metric' => '']);
        $this->assertNull($param->refresh()->metric);
    }

    public function test_country_codes_come_from_the_shared_list(): void
    {
        $this->assertSame('GMB', Countries::code('Gambia'));
        $this->assertSame('BFA', Countries::code('Burkina Faso'));
        $this->assertSame('CIV', Countries::code('Ivory Coast'));   // older spelling still resolves
        $this->assertSame('ATL', Countries::code('Atlantis'));      // unknown: first letters, as before

        $site = Site::factory()->create(['status' => 'active', 'country' => 'Gambia']);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('GMB')->assertDontSee('GAM');

        // Every country selector offers the same list.
        foreach ([route('admin.sites.create'), route('admin.sites.edit', $site), route('admin.users.create')] as $page) {
            $this->get($page)->assertOk()->assertSee('Guinea-Bissau')->assertSee('South Africa');
        }
    }
}
