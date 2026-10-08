<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * thingsboard:sync runs unattended every ten minutes: a mapping or dedup bug
 * here shows up as wrong, missing or doubled points on the partners' charts
 * with nobody watching the command output.
 */
class ThingsBoardSyncTest extends TestCase
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
    }

    private function makeSite(?string $prefix = 'UTG'): Site
    {
        return Site::factory()->create(['status' => 'active', 'thingsboard_prefix' => $prefix]);
    }

    private function ms(string $time): int
    {
        return Carbon::parse($time)->getTimestampMs();
    }

    /** @param array<string, mixed> $telemetry device name => timeseries body, or a ready-made fake response/sequence */
    private function fakeThingsBoard(array $telemetry): void
    {
        $devices = [];
        $fakes   = [
            'tb.test/api/auth/login' => Http::response(['token' => 'jwt-token']),
            'tb.test/api/auth/user'  => Http::response(['authority' => 'TENANT_ADMIN']),
        ];

        foreach ($telemetry as $name => $series) {
            $devices[] = ['id' => ['id' => $name], 'name' => $name, 'type' => 'x'];
            $fakes["tb.test/api/plugins/telemetry/DEVICE/{$name}/values/timeseries*"] = is_array($series) ? Http::response($series) : $series;
        }

        $fakes['tb.test/api/tenant/devices*'] = Http::response(['data' => $devices, 'hasNext' => false]);

        Http::fake($fakes);
    }

    private function param(Site $site, string $slug): SiteParameter
    {
        return SiteParameter::where('slug', $slug)
            ->whereHas('category', fn ($q) => $q->where('site_id', $site->id))
            ->firstOrFail();
    }

    public function test_creates_the_mapped_parameters_and_stores_their_readings(): void
    {
        $site = $this->makeSite();

        $this->fakeThingsBoard([
            'UTG-APV-Humidity-1-Shadow' => [
                'humidity'     => [['ts' => $this->ms('2026-10-07 14:20:00'), 'value' => '22.78'], ['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '23.1']],
                'conductivity' => [['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '12.0']],
                'temperature'  => [['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '33.07']],
            ],
            'UTG-Reference-Valve-2' => [
                'valve_state' => [['ts' => $this->ms('2026-10-07 14:45:00'), 'value' => 'true']],
            ],
        ]);

        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('2 device(s), 5 reading(s) stored, 0 rejected, 4 parameter(s) created.')
            ->assertSuccessful();

        $moisture = $this->param($site, 'apv_humidity_1_shadow_moisture');
        $this->assertSame('APV 1 Shadow – Moisture', $moisture->name);
        $this->assertSame('%', $moisture->unit);
        $this->assertSame('irrigation', $moisture->category->slug);
        $this->assertSame(60, $moisture->category->offline_threshold_minutes);
        $this->assertSame('APV field', $moisture->group->name);
        $this->assertSame(
            ['2026-10-07 14:20:00' => 22.78, '2026-10-07 14:40:00' => 23.1],
            SensorReading::where('site_parameter_id', $moisture->id)->orderBy('read_at')->get()
                ->mapWithKeys(fn ($r) => [$r->read_at->toDateTimeString() => round($r->value, 2)])->all(),
        );

        $valve = $this->param($site, 'reference_valve_2_valve_state');
        $this->assertSame('switch', $valve->data_type);
        $this->assertSame('readonly', $valve->control_type);
        $this->assertSame('Reference field', $valve->group->name);
        $this->assertSame(1.0, $valve->latestReading->value);
    }

    public function test_new_parameters_are_ordered_by_device_named_by_position_and_charted_by_unit(): void
    {
        $site  = $this->makeSite();
        $point = [['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '20']];

        // ThingsBoard lists devices in no useful order.
        $this->fakeThingsBoard([
            'UTG-Reference-Humidity-1'  => ['humidity' => $point],
            'UTG-APV-Valve-1'           => ['valve_state' => $point],
            'UTG-APV-Humidity-1-Sun'    => ['humidity' => $point],
            'UTG-APV-Humidity-1-Shadow' => ['humidity' => $point],
        ]);

        $this->artisan('thingsboard:sync')->assertSuccessful();
        $this->artisan('thingsboard:sync')->assertSuccessful();

        $irrigation = $site->categories()->where('slug', 'irrigation')->firstOrFail();

        $this->assertSame([
            'APV 1 Shadow – Moisture', 'APV 1 Shadow – Conductivity', 'APV 1 Shadow – Temperature',
            'APV 1 Sun – Moisture', 'APV 1 Sun – Conductivity', 'APV 1 Sun – Temperature',
            'APV 1 – Valve state',
            'Reference 1 – Moisture', 'Reference 1 – Conductivity', 'Reference 1 – Temperature',
        ], $irrigation->parameters()->pluck('name')->all());

        // One chart per unit, valves (not plottable) in none, nothing doubled by the second run.
        $this->assertSame(
            ['Soil moisture (%)', 'Soil conductivity (µS/cm)', 'Soil temperature (°C)'],
            $irrigation->charts()->pluck('title')->all(),
        );

        $moisture = $irrigation->charts()->where('title', 'Soil moisture (%)')->first();
        $this->assertSame(
            ['APV 1 Shadow – Moisture' => false, 'APV 1 Sun – Moisture' => false, 'Reference 1 – Moisture' => true],
            $moisture->parameters->mapWithKeys(fn ($p) => [$p->name => (bool) $p->pivot->dashed])->all(),
        );
        $this->assertCount(3, $moisture->parameters->pluck('pivot.color')->unique());
    }

    public function test_tank_height_is_also_stored_as_a_volume_in_litres(): void
    {
        $site = $this->makeSite('AfriFarm');

        $this->fakeThingsBoard([
            'AfriFarm-General-Pressure-0' => [
                'tank_level' => [['ts' => $this->ms('2026-10-07 14:26:43'), 'value' => '26.125']],
            ],
        ]);

        $this->artisan('thingsboard:sync')->assertSuccessful();

        $this->assertEqualsWithDelta(26.125, $this->param($site, 'general_pressure_0_tank_level')->latestReading->value, 0.001);
        // Math.round(Math.PI * 180 * 180 * 26.125 / 1000)
        $this->assertSame(2659.0, $this->param($site, 'general_pressure_0_tank_volume')->latestReading->value);
        $this->assertSame('water', $this->param($site, 'general_pressure_0_tank_volume')->category->slug);
    }

    public function test_a_second_run_only_asks_for_and_stores_what_is_new(): void
    {
        $site = $this->makeSite();
        $first = ['ts' => $this->ms('2026-10-07 14:20:00') + 500, 'value' => '10'];

        // On the second run ThingsBoard hands the already-stored point back along with a new one.
        $this->fakeThingsBoard(['UTG-APV-Flow-0' => Http::sequence()
            ->push(['Flow_level' => [$first]])
            ->push(['Flow_level' => [$first, ['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '15']]]),
        ]);

        $this->artisan('thingsboard:sync')->assertSuccessful();
        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('1 reading(s) stored, 0 rejected, 0 parameter(s) created.')
            ->assertSuccessful();

        $flow = $this->param($site, 'apv_flow_0_total_flow');
        $this->assertSame(2, SensorReading::where('site_parameter_id', $flow->id)->count());

        // First run reaches back the configured 7 days; the second resumes after the stored second.
        $starts = Http::recorded(fn (Request $r) => str_contains($r->url(), '/values/timeseries'))
            ->map(fn ($pair) => (int) $pair[0]['startTs'])->values()->all();
        $this->assertSame([$this->ms('2026-09-30 15:00:00') + 1, $this->ms('2026-10-07 14:20:01')], $starts);
    }

    public function test_ignores_devices_of_other_sites_and_unmapped_zones_or_sensors(): void
    {
        $site = $this->makeSite();

        $point = [['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '1']];
        $this->fakeThingsBoard([
            'AfriFarm-APV-Valve-1'     => ['valve_state' => $point],
            'UTG-Business-Valve-1'     => ['valve_state' => $point],
            'UTG-Spare-Turbidity-1'    => ['turbidity' => $point],
            'UTG-General-collection-0' => ['tank_level' => $point],
            'UTG-APV-Valve-1'          => ['valve_state' => $point, 'fake_signal' => $point],
        ]);

        $this->artisan('thingsboard:sync')->assertSuccessful();

        $this->assertSame(
            ['apv_valve_1_valve_state'],
            SiteParameter::whereHas('category', fn ($q) => $q->where('site_id', $site->id))->pluck('slug')->all(),
        );
        $this->assertSame(1, SensorReading::count());
    }

    public function test_respects_what_the_admin_changed_on_a_synced_parameter(): void
    {
        $site = $this->makeSite();
        $series = [
            'UTG-General-Ultrasonic-0' => ['tank_distance' => [
                ['ts' => $this->ms('2026-10-07 14:20:00'), 'value' => '114.6'],
                ['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '900'],
                ['ts' => $this->ms('2026-10-07 14:50:00'), 'value' => 'n/a'],
            ]],
            'UTG-General-Turbidity-0' => ['turbidity' => [['ts' => $this->ms('2026-10-07 14:40:00'), 'value' => '3']]],
        ];

        $this->fakeThingsBoard($series);
        $this->artisan('thingsboard:sync')->assertSuccessful();
        SensorReading::query()->delete();

        $this->param($site, 'general_ultrasonic_0_water_level')->update(['name' => 'Borehole', 'max_value' => 400]);
        $this->param($site, 'general_turbidity_0_turbidity')->update(['is_active' => false]);

        $this->fakeThingsBoard($series);
        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('1 reading(s) stored, 2 rejected, 0 parameter(s) created.')
            ->assertSuccessful();

        $this->assertSame('Borehole', $this->param($site, 'general_ultrasonic_0_water_level')->name);
        $this->assertSame(0, SensorReading::where('site_parameter_id', $this->param($site, 'general_turbidity_0_turbidity')->id)->count());
    }

    public function test_a_long_gap_is_caught_up_in_one_run_page_by_page(): void
    {
        $site  = $this->makeSite();
        $start = $this->ms('2026-10-01 00:00:00');
        $page  = fn (int $from, int $count) => ['valve_state' => array_map(
            fn ($i) => ['ts' => $start + ($from + $i) * 60000, 'value' => 'true'],
            range(0, $count - 1),
        )];

        $this->fakeThingsBoard(['UTG-APV-Valve-1' => Http::sequence()->push($page(0, 1000))->push($page(1000, 1000))->push($page(2000, 300))]);

        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('2300 reading(s) stored')
            ->assertSuccessful();

        $this->assertSame(2300, SensorReading::distinct()->count('read_at'));

        $starts = Http::recorded(fn (Request $r) => str_contains($r->url(), '/values/timeseries'))
            ->map(fn ($pair) => (int) $pair[0]['startTs'])->values()->all();
        $this->assertSame([$start + 999 * 60000 + 1, $start + 1999 * 60000 + 1], array_slice($starts, 1));
    }

    public function test_one_failing_device_does_not_block_the_others_and_is_reported(): void
    {
        Sleep::fake();
        Log::spy();
        $site = $this->makeSite();
        $site->forceFill(['thingsboard_synced_at' => '2026-10-07 14:50:00'])->save();

        $this->fakeThingsBoard([
            'UTG-APV-Valve-1' => Http::response('boom', 500),
            'UTG-APV-Valve-2' => ['valve_state' => [['ts' => $this->ms('2026-10-07 14:45:00'), 'value' => 'false']]],
        ]);

        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('1 reading(s) stored')
            ->assertFailed();

        $this->assertSame(0.0, $this->param($site, 'apv_valve_2_valve_state')->latestReading->value);

        $site->refresh();
        $this->assertStringStartsWith('UTG-APV-Valve-1: ', $site->thingsboard_sync_error);
        $this->assertSame('2026-10-07 14:50:00', $site->thingsboard_synced_at->toDateTimeString());
        $this->assertSame('failed', $site->thingsboardSyncState());
        Log::shouldHaveReceived('error')->once();

        // The server error was retried before giving up.
        $this->assertCount(3, Http::recorded(fn (Request $r) => str_contains($r->url(), 'UTG-APV-Valve-1')));
    }

    public function test_sync_state_goes_from_failed_to_ok_to_late(): void
    {
        Log::spy();
        $site = $this->makeSite();

        Http::fake(['tb.test/api/auth/login' => Http::response(['message' => 'Invalid username or password'], 401)]);
        $this->artisan('thingsboard:sync')->assertFailed();
        $this->assertSame('failed', $site->refresh()->thingsboardSyncState());

        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->fakeThingsBoard([]);
        $this->artisan('thingsboard:sync')->assertSuccessful();
        $this->assertSame('ok', $site->refresh()->thingsboardSyncState());
        $this->assertNull($site->thingsboard_sync_error);

        // Nothing ran for a while — e.g. the server cron stopped.
        $this->travel(31)->minutes();
        $this->assertSame('late', $site->refresh()->thingsboardSyncState());

        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.sites'))->assertOk()->assertSee('ThingsBoard late');
        $this->assertNull($this->makeSite(null)->thingsboardSyncState());
    }

    public function test_does_not_run_while_another_sync_holds_the_lock(): void
    {
        $this->makeSite();
        Http::fake();

        $lock = Cache::lock('thingsboard-sync', 60);
        $lock->get();

        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('Another ThingsBoard sync is still running')
            ->assertSuccessful();

        Http::assertNothingSent();
        $lock->release();
    }

    public function test_sites_without_a_prefix_are_left_alone(): void
    {
        $this->makeSite(null);
        Http::fake();

        $this->artisan('thingsboard:sync')
            ->expectsOutputToContain('nothing to sync')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_duplicating_a_site_does_not_copy_its_prefix(): void
    {
        $site  = $this->makeSite();
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.sites.duplicate', $site))->assertRedirect();

        $this->assertSame(['UTG', null], Site::orderBy('id')->pluck('thingsboard_prefix')->all());
    }
}
