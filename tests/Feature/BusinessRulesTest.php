<?php

namespace Tests\Feature;

use App\Models\ManualReading;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Rules found missing or contradicted by the business-logic audit: who may
 * change what on a site, what a reading's timestamp may be, that a reading
 * exists once, and that one silent sensor doesn't slow every sync down.
 */
class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 12:00:00');
    }

    private function site(array $attrs = []): Site
    {
        return Site::factory()->create(['status' => 'active'] + $attrs);
    }

    private function parameter(Site $site, string $category, array $attrs = []): SiteParameter
    {
        $cat = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => $category],
            ['name' => ucfirst($category), 'is_active' => true, 'sort_order' => 0],
        );

        return SiteParameter::factory()->create(['site_category_id' => $cat->id, 'is_active' => true] + $attrs);
    }

    // ── who may change things on a site ──────────────────────────────

    public function test_the_role_given_on_a_site_decides_what_a_user_may_change_there(): void
    {
        $site   = $this->site();
        $manual = $this->parameter($site, 'agriculture', ['input_type' => 'manual', 'slug' => 'plant_height']);
        $switch = $this->parameter($site, 'irrigation', ['data_type' => 'switch', 'control_type' => 'controllable', 'slug' => 'pump']);

        // An agent account, but only an observer on this site...
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active']);
        $site->users()->attach($agent->id, ['role' => User::ROLE_OBSERVER]);
        // ...and an observer account that was made agent on this site.
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);
        $site->users()->attach($observer->id, ['role' => User::ROLE_AGENT]);

        $reading = ['reading_date' => '2026-10-13', 'readings' => [['param_id' => $manual->id, 'value' => 12]]];

        $this->actingAs($agent)->get(route('dashboard.site', $site))->assertOk();   // can see
        $this->post(route('manual-readings.store', $site), $reading)->assertForbidden();
        $this->post(route('actuators.toggle', [$site, $switch]), ['state' => 1])->assertForbidden();
        $this->assertSame(0, ManualReading::count());

        $this->actingAs($observer)->post(route('manual-readings.store', $site), $reading)->assertRedirect();
        $this->post(route('actuators.toggle', [$site, $switch]), ['state' => 1])->assertRedirect();
        $this->assertSame(1, ManualReading::count());
        $this->assertSame(1, $switch->actuatorCommand->desired_state);
    }

    public function test_a_site_owner_without_an_explicit_assignment_keeps_the_accounts_own_role(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active']);
        $viewer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);

        $this->assertTrue($owner->canWriteTo($this->site(['user_id' => $owner->id])));
        $this->assertFalse($viewer->canWriteTo($this->site(['user_id' => $viewer->id])));
        $this->assertFalse($owner->canWriteTo($this->site()));   // someone else's site
        $this->assertTrue(User::factory()->create(['role' => User::ROLE_ADMIN])->canWriteTo($this->site()));
    }

    // ── what a device may send ───────────────────────────────────────

    public function test_the_api_refuses_timestamps_in_the_future_or_unreadable(): void
    {
        $site = $this->site();
        $this->parameter($site, 'solar', ['slug' => 'solar_output']);
        $post = fn (array $body) => $this->postJson("/api/sensors/{$site->slug}/solar", $body, ['X-API-Key' => $site->api_key]);

        $post(['solar_output' => 4.2, 'read_at' => '2030-01-01 00:00:00'])
            ->assertStatus(422)->assertJson(['success' => false, 'error' => 'read_at is in the future; check the device clock.']);
        $post(['solar_output' => 4.2, 'read_at' => 'yesterday-ish'])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(0, SensorReading::count());

        // A few minutes of clock drift, and any past instant, are fine.
        $post(['solar_output' => 4.2, 'read_at' => '2026-10-13 12:05:00'])->assertCreated();
        $post(['solar_output' => 4.1, 'read_at' => '2026-10-13T11:00:00+01:00'])->assertCreated();
        $this->assertSame(
            ['2026-10-13 10:00:00', '2026-10-13 12:05:00'],
            SensorReading::orderBy('read_at')->get()->map(fn ($r) => $r->read_at->toDateTimeString())->all(),
        );
    }

    public function test_a_parameter_has_one_reading_per_instant(): void
    {
        $site = $this->site();
        $this->parameter($site, 'solar', ['slug' => 'solar_output']);
        $post = fn (float $value) => $this->postJson("/api/sensors/{$site->slug}/solar",
            ['solar_output' => $value, 'read_at' => '2026-10-13 11:00:00'], ['X-API-Key' => $site->api_key]);

        $post(4.2)->assertCreated();
        $post(4.5)->assertCreated();   // the device re-sends the same instant: it corrects, it doesn't add

        $this->assertSame([4.5], SensorReading::pluck('value')->all());
    }

    // ── one silent sensor must not slow every sync down ──────────────

    public function test_a_stopped_licor_sensor_does_not_make_every_run_refetch_from_where_it_stopped(): void
    {
        config(['licor.url' => 'http://licor.test/v2', 'licor.token' => 'secret']);
        $site = $this->site();
        $site->forceFill(['licor_device_serial' => '22490036'])->save();

        $minute = fn (string $time, float $v) => [Carbon::parse($time)->getTimestampMs(), $v];
        $alive  = [];
        for ($t = Carbon::parse('2026-10-08 12:00:00'); $t->lt('2026-10-13 11:55:00'); $t->addMinute()) {
            $alive[] = $minute($t->toDateTimeString(), 25);
        }
        // The second sensor stopped five days ago, after ten minutes.
        $stopped = array_slice($alive, 0, 11);

        Http::fake([
            'licor.test/v2/devices*' => Http::response(['devices' => [['deviceSerialNumber' => '22490036', 'sensors' => [
                ['sensorSerialNumber' => 'A-1', 'measurementType' => 'Temperature'],
                ['sensorSerialNumber' => 'B-1', 'measurementType' => 'RH'],
            ]]]]),
            'licor.test/v2/data*' => function (Request $request) use ($alive, $stopped) {
                $in = fn (array $records) => array_values(array_filter($records, fn ($r) => $r[0] >= $request['startTime'] && $r[0] <= $request['endTime']));

                return Http::response(['moreResults' => false, 'sensors' => [
                    ['sensorSerialNumber' => 'A-1', 'data' => [['records' => $in($alive)]]],
                    ['sensorSerialNumber' => 'B-1', 'data' => [['records' => $in($stopped)]]],
                ]]);
            },
        ]);

        $requests = fn () => Http::recorded(fn (Request $r) => str_contains($r->url(), '/data'))->count();

        $this->artisan('licor:sync')->assertSuccessful();   // first run of the day: catches everything up
        $afterFirst = $requests();

        $this->artisan('licor:sync')->assertSuccessful();   // ordinary run
        $this->assertSame(1, $requests() - $afterFirst, 'an ordinary run should fetch one recent window, not five days');

        $last = Http::recorded(fn (Request $r) => str_contains($r->url(), '/data'))->last()[0];
        $this->assertGreaterThanOrEqual(Carbon::parse('2026-10-13 05:00:00')->getTimestampMs(), (int) $last['startTime']);

        // Nothing was stored twice along the way.
        $this->assertSame(SensorReading::count(), SensorReading::distinct()->count(\DB::raw("site_parameter_id || '@' || read_at")));
    }

    public function test_a_thingsboard_key_that_never_reported_does_not_make_the_others_refetch_their_history(): void
    {
        config(['thingsboard.url' => 'http://tb.test', 'thingsboard.username' => 'u', 'thingsboard.password' => 'p', 'thingsboard.lookback_days' => 60]);
        $site = $this->site(['thingsboard_prefix' => 'UTG']);

        $point = fn (string $time) => ['ts' => Carbon::parse($time)->getTimestampMs(), 'value' => '20'];

        Http::fake([
            'tb.test/api/auth/login'      => Http::response(['token' => 't']),
            'tb.test/api/auth/user'       => Http::response(['authority' => 'TENANT_ADMIN']),
            'tb.test/api/tenant/devices*' => Http::response(['hasNext' => false, 'data' => [['id' => ['id' => 'd1'], 'name' => 'UTG-APV-Humidity-1-Sun']]]),
            // Moisture and temperature report; this probe has no conductivity channel.
            'tb.test/api/plugins/telemetry/*' => Http::sequence()
                ->push(['humidity' => [$point('2026-10-13 11:00:00'), $point('2026-10-13 11:00:00')], 'temperature' => [$point('2026-10-13 11:00:00')]])
                ->whenEmpty(Http::response([])),
        ]);

        $this->artisan('thingsboard:sync')->assertSuccessful();
        // The same point twice in one answer is one reading.
        $this->assertSame(2, SensorReading::count());

        $before = Http::recorded()->count();
        $this->artisan('thingsboard:sync')->assertSuccessful();

        $starts = Http::recorded()->slice($before)
            ->filter(fn ($pair) => str_contains($pair[0]->url(), '/values/timeseries'))
            ->mapWithKeys(fn ($pair) => [$pair[0]['keys'] => (int) $pair[0]['startTs']]);

        // The keys that have data resume from their last reading...
        $this->assertSame(Carbon::parse('2026-10-13 11:00:01')->getTimestampMs(), $starts['humidity,temperature']);
        // ...only the empty one is still asked from the start of the lookback.
        $this->assertLessThan(Carbon::parse('2026-09-01')->getTimestampMs(), $starts['conductivity']);
    }

    // ── raw data and exports ─────────────────────────────────────────

    public function test_raw_data_opens_on_24_hours_and_pages_without_loading_everything(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $site  = $this->site();
        $param = $this->parameter($site, 'weather', ['slug' => 'air_temp', 'name' => 'Air temp']);

        // One reading every 10 minutes for the last 10 hours: 60 timestamps, 3 pages of 20.
        for ($i = 0; $i < 60; $i++) {
            SensorReading::factory()->create([
                'site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => $i,
                'read_at' => Carbon::parse('2026-10-13 11:50:00')->subMinutes(10 * $i),
            ]);
        }

        $url = route('dashboard.raw-data', $site->slug);

        // No period given: the 24-hour default finds them (the old 1-hour default showed 6).
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSee('Page 1 of 3')->assertSee('2026-10-13 11:50:00')->assertDontSee('2026-10-13 08:30:00');
        $this->get($url . '?page=3')->assertOk()
            ->assertSee('Page 3 of 3')->assertSee('2026-10-13 02:00:00')->assertDontSee('2026-10-13 11:50:00');
    }

    public function test_csv_export_includes_manual_text_values(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $site  = $this->site();
        $crop  = $this->parameter($site, 'agriculture', ['input_type' => 'manual', 'data_type' => 'string', 'slug' => 'crop_type', 'name' => 'Crop type', 'unit' => null]);

        ManualReading::create([
            'site_id' => $site->id, 'site_parameter_id' => $crop->id, 'user_id' => $admin->id,
            'value' => 'Onion', 'reading_date' => '2026-10-13 09:00:00',
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('export.category.csv', [$site->slug, 'agriculture']) . '?hours=24')
            ->assertOk()->streamedContent();

        $this->assertStringContainsString('Crop type', $csv);
        $this->assertStringContainsString('Onion', $csv);
    }
}
