<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use App\Support\Zones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The Analysis page puts numbers in front of researchers: these tests pin
 * the calculations on data whose answer is known in advance.
 */
class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-18 12:00:00');
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
    }

    private function site(string $name): Site
    {
        return Site::factory()->create(['name' => $name, 'status' => 'active']);
    }

    private function param(Site $site, string $metric, ?string $zone, string $unit = '°C', string $category = 'weather'): SiteParameter
    {
        $cat = SiteCategory::firstOrCreate(['site_id' => $site->id, 'slug' => $category], ['name' => ucfirst($category), 'is_active' => true, 'sort_order' => 0]);

        return SiteParameter::factory()->create([
            'site_category_id' => $cat->id, 'metric' => $metric, 'zone' => $zone, 'unit' => $unit,
            'input_type' => 'sensor', 'is_active' => true, 'data_type' => 'float',
        ]);
    }

    /** One reading every 10 minutes on 17 October, from $fromHour to $toHour (exclusive). */
    private function day(SiteParameter $p, callable $value, int $fromHour = 0, int $toHour = 24): void
    {
        $rows = [];
        for ($m = $fromHour * 60; $m < $toHour * 60; $m += 10) {
            $rows[] = ['site_id' => $p->category->site_id, 'site_parameter_id' => $p->id, 'value' => $value(intdiv($m, 60)),
                'read_at' => Carbon::parse('2026-10-17 00:00:00')->addMinutes($m), 'created_at' => now(), 'updated_at' => now()];
        }
        SensorReading::insert($rows);
    }

    private function analyse(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->getJson(route('analysis.data', $params + ['from' => '2026-10-17', 'to' => '2026-10-17']));
    }

    public function test_zones_of_a_site_are_compared_on_common_hours_with_probes_averaged(): void
    {
        $site = $this->site('UTG');
        $this->day($this->param($site, 'air_temperature', Zones::REFERENCE), fn () => 30);
        $this->day($this->param($site, 'air_temperature', Zones::REFERENCE), fn () => 32);           // reference = mean of both: 31
        $this->day($this->param($site, 'air_temperature', Zones::UNDER_PV), fn () => 29, 0, 12);     // only the morning

        $r = $this->analyse(['mode' => 'site', 'site' => $site->id, 'quantity' => 'air_temperature', 'step' => 'hour'])
            ->assertOk()->json();

        $this->assertSame(['Reference', 'Under PV'], array_column($r['series'], 'label'));
        $this->assertSame(2, $r['series'][0]['sensors']);
        $this->assertEquals(31, $r['series'][0]['stats']['mean']);
        $this->assertEquals(100, $r['series'][0]['stats']['coverage']);   // 24 of 24 hours
        $this->assertEquals(50, $r['series'][1]['stats']['coverage']);    // 12 of 24 hours

        $c = $r['series'][1]['comparison'];
        $this->assertSame(12, $c['common']);
        $this->assertEquals(-2, $c['difference']);
        $this->assertNull($c['percent']);                                   // no % for temperatures
        $this->assertStringContainsString('Under PV averages 29 °C against 31 °C at Reference: −2 °C, over 12 common hours.', implode(' ', $r['findings']));
        $this->assertStringContainsString('covers only 50 %', implode(' ', $r['findings']));
        $this->assertNull($r['series'][0]['gaps']);
        $this->assertEquals(-2, $r['series'][1]['gaps'][0]);
    }

    public function test_light_is_compared_as_a_daily_light_integral_and_a_share_of_the_reference(): void
    {
        $site = $this->site('UTG');
        $this->day($this->param($site, 'par_reference', Zones::REFERENCE, 'µmol/m²/s', 'solar'), fn () => 1000);
        $this->day($this->param($site, 'par_under_pv', Zones::UNDER_PV, 'µmol/m²/s', 'solar'), fn () => 400);

        $r = $this->analyse(['mode' => 'site', 'site' => $site->id, 'quantity' => 'par', 'step' => 'day'])->assertOk()->json();

        $this->assertSame('Daily light integral (DLI)', $r['title']);
        $this->assertSame('mol/m²/day', $r['unit']);
        $this->assertEquals(86.4, $r['series'][0]['stats']['mean']);      // 1000 µmol/m²/s over 24 h
        $this->assertEquals(34.56, $r['series'][1]['stats']['mean']);
        $this->assertEquals(40, $r['series'][1]['comparison']['ratio']);
        $this->assertEquals(-60, $r['series'][1]['comparison']['percent']);
        $this->assertStringContainsString('Under PV receives 40 % of the light measured at Reference', $r['findings'][0]);
        // The hour-of-day gap stays in the sensors' unit even on a daily step.
        $this->assertStringContainsString('−600 µmol/m²/s on average at that hour', implode(' ', $r['findings']));
    }

    public function test_rain_is_summed_and_sites_are_compared_in_one_zone(): void
    {
        $a = $this->site('AfriFarm');
        $b = $this->site('Fass');
        $this->day($this->param($a, 'rain', Zones::REFERENCE, 'mm'), fn () => 0.1);    // 144 readings: 14.4 mm
        $this->day($this->param($b, 'rain', Zones::REFERENCE, 'mm'), fn () => 0.05);   // 7.2 mm
        $this->day($this->param($b, 'rain', Zones::UNDER_PV, 'mm'), fn () => 9);       // other zone: ignored

        $r = $this->analyse(['mode' => 'sites', 'sites' => [$a->id, $b->id], 'zone' => 'reference', 'quantity' => 'rain', 'step' => 'day'])
            ->assertOk()->json();

        $this->assertSame(['AfriFarm', 'Fass'], array_column($r['series'], 'label'));
        $this->assertEquals(14.4, $r['series'][0]['stats']['mean']);
        $this->assertEquals(7.2, $r['series'][1]['stats']['mean']);
        $this->assertEquals(-50, $r['series'][1]['comparison']['percent']);
    }

    public function test_nothing_to_compare_and_too_long_periods_are_explained(): void
    {
        $site = $this->site('UTG');
        $this->day($this->param($site, 'air_temperature', Zones::REFERENCE), fn () => 30);

        $this->analyse(['mode' => 'site', 'site' => $site->id, 'quantity' => 'air_temperature', 'step' => 'hour'])
            ->assertStatus(422)->assertJsonPath('errors.quantity.0', 'This site measures this quantity in fewer than two zones: there is nothing to compare.');

        $period = fn (string $step, string $from) => $this->actingAs($this->admin)->getJson(route('analysis.data',
            ['mode' => 'site', 'site' => $site->id, 'quantity' => 'air_temperature', 'step' => $step, 'from' => $from, 'to' => '2026-10-17']));

        // A whole season compares in daily values, not in hourly ones; nothing beyond a year.
        $period('day', '2026-06-01')->assertStatus(422)->assertJsonMissingValidationErrors('to');
        $period('hour', '2026-06-01')->assertStatus(422)->assertJsonPath('errors.to.0', 'With hourly values a period is limited to 92 days: choose daily values for a longer one.');
        $period('day', '2025-06-01')->assertStatus(422)->assertJsonValidationErrors('to');
    }

    public function test_only_the_sites_a_user_may_open_can_be_analysed(): void
    {
        $mine  = $this->site('Mine');
        $other = $this->site('Other');
        foreach ([$mine, $other] as $s) {
            $this->day($this->param($s, 'air_temperature', Zones::REFERENCE), fn () => 30);
            $this->day($this->param($s, 'air_temperature', Zones::UNDER_PV), fn () => 29);
        }
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);
        $mine->users()->attach($observer->id, ['role' => User::ROLE_OBSERVER]);

        $this->actingAs($observer)->get(route('analysis'))->assertOk()->assertSee('Mine')->assertDontSee('"Other"', false);
        $base = ['mode' => 'site', 'quantity' => 'air_temperature', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17'];
        $this->actingAs($observer)->getJson(route('analysis.data', $base + ['site' => $mine->id]))->assertOk();
        $this->actingAs($observer)->getJson(route('analysis.data', $base + ['site' => $other->id]))->assertForbidden();
    }

    public function test_the_data_downloads_as_csv_and_the_report_renders(): void
    {
        $site = $this->site('UTG');
        $this->day($this->param($site, 'air_temperature', Zones::REFERENCE), fn ($h) => 25 + $h);
        $this->day($this->param($site, 'air_temperature', Zones::UNDER_PV), fn ($h) => 24 + $h);
        $q = ['mode' => 'site', 'site' => $site->id, 'quantity' => 'air_temperature', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17'];

        $csv = $this->actingAs($this->admin)->get(route('analysis.csv', $q))->assertOk()->streamedContent();
        $this->assertStringContainsString("time_utc,Reference,\"Under PV\"", $csv);
        $this->assertStringContainsString('"2026-10-17 05:00",30,29', $csv);

        $this->actingAs($this->admin)->get(route('analysis.report', $q))->assertOk()
            ->assertSee('Air temperature by zone at UTG, hourly values')
            ->assertSee('Method and data quality');
    }

    public function test_zones_are_inferred_from_what_the_sources_record(): void
    {
        $this->assertSame(Zones::UNDER_PV, Zones::infer('par_under_pv', 'PAR'));
        $this->assertSame(Zones::BETWEEN_PV, Zones::infer('air_temperature', 'Between PV'));
        $this->assertSame(Zones::REFERENCE, Zones::infer('soil_moisture', 'Reference field'));
        $this->assertSame(Zones::APV, Zones::infer('soil_moisture', 'APV field'));
        $this->assertNull(Zones::infer('tank_volume', 'Entire farm'));
    }
}
