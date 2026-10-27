<?php

namespace Tests\Feature;

use App\Models\ManualReading;
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
 * Analysis, second part: field measurements compared between plots, two
 * periods of the same place, and any parameters together with their
 * correlation.
 */
class AnalysisV2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-18 12:00:00');
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $this->site  = Site::factory()->create(['name' => 'UTG', 'status' => 'active']);
    }

    private function param(string $category, array $attrs): SiteParameter
    {
        $cat = SiteCategory::firstOrCreate(['site_id' => $this->site->id, 'slug' => $category], ['name' => ucfirst($category), 'is_active' => true, 'sort_order' => 0]);

        return SiteParameter::factory()->create($attrs + ['site_category_id' => $cat->id, 'is_active' => true, 'data_type' => 'float', 'input_type' => 'sensor']);
    }

    /** Hourly readings from $start for $hours hours. */
    private function hours(SiteParameter $p, string $start, int $hours, callable $value): void
    {
        $rows = [];
        for ($h = 0; $h < $hours; $h++) {
            $rows[] = ['site_id' => $this->site->id, 'site_parameter_id' => $p->id, 'value' => $value($h),
                'read_at' => Carbon::parse($start)->addHours($h), 'created_at' => now(), 'updated_at' => now()];
        }
        SensorReading::insert($rows);
    }

    public function test_field_measurements_record_their_plot_and_are_compared_between_plots(): void
    {
        $yield = $this->param('agriculture', ['name' => 'Yield', 'slug' => 'yield', 'metric' => 'yield', 'unit' => 'kg/ha', 'input_type' => 'manual']);

        // Entered through the form, with the plot.
        $this->actingAs($this->admin)->post(route('manual-readings.store', $this->site), [
            'reading_date' => '2026-10-15', 'zone' => 'reference', 'readings' => [['param_id' => $yield->id, 'value' => '2000']],
        ])->assertRedirect();
        $this->actingAs($this->admin)->post(route('manual-readings.store', $this->site), [
            'reading_date' => '2026-10-15', 'zone' => 'apv', 'readings' => [['param_id' => $yield->id, 'value' => '1700']],
        ])->assertRedirect();
        $this->assertSame(['apv', 'reference'], ManualReading::orderBy('zone')->pluck('zone')->all());

        // A plot that does not exist is refused.
        $this->actingAs($this->admin)->post(route('manual-readings.store', $this->site), [
            'reading_date' => '2026-10-15', 'zone' => 'mars', 'readings' => [['param_id' => $yield->id, 'value' => '1']],
        ])->assertSessionHasErrors('zone');

        $r = $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'site', 'site' => $this->site->id, 'quantity' => 'yield', 'step' => 'hour', 'from' => '2026-10-01', 'to' => '2026-10-18',
        ]))->assertOk()->json();

        $this->assertSame('day', $r['step']);   // field measurements are compared per day
        $this->assertSame(['Reference plot', 'APV plot (under the panels)'], array_column($r['series'], 'label'));
        $this->assertEquals(-15, $r['series'][1]['comparison']['percent']);
        $this->assertStringContainsString('APV plot (under the panels) averages 1 700 kg/ha against 2 000 kg/ha at Reference plot: −300 kg/ha (−15 %)', $r['findings'][0]);
    }

    public function test_two_periods_of_the_same_place_are_compared_day_by_day(): void
    {
        $moisture = $this->param('irrigation', ['name' => 'Moisture', 'metric' => 'soil_moisture', 'zone' => Zones::APV, 'unit' => '%']);
        $this->hours($moisture, '2026-09-01 00:00', 72, fn () => 30);   // period A: 1–3 Sept, 30 %
        $this->hours($moisture, '2026-10-01 00:00', 48, fn () => 20);   // period B: 1–3 Oct, only 2 days, 20 %

        $r = $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'periods', 'site' => $this->site->id, 'quantity' => 'soil_moisture', 'zone' => 'apv', 'step' => 'day',
            'from' => '2026-09-01', 'to' => '2026-09-03', 'from2' => '2026-10-01', 'to2' => '2026-10-03',
        ]))->assertOk()->json();

        $this->assertTrue($r['relative']);
        $this->assertSame([0, 1, 2], $r['labels']);                         // day 1, 2, 3 of each period
        $this->assertSame('Period A (01 Sep 2026 – 03 Sep 2026)', $r['series'][0]['label']);
        $this->assertSame(2, $r['series'][1]['comparison']['common']);      // day 3 of B has no data
        $this->assertEquals(-10, $r['series'][1]['comparison']['difference']);
        $this->assertStringContainsString('over 2 matching days', $r['findings'][0]);
    }

    public function test_any_parameters_are_shown_together_with_their_correlation(): void
    {
        $flow = $this->param('irrigation', ['name' => 'Daily flow', 'metric' => 'total_flow', 'unit' => 'L']);
        $moisture = $this->param('irrigation', ['name' => 'Moisture', 'metric' => 'soil_moisture', 'zone' => Zones::APV, 'unit' => '%']);
        $this->hours($flow, '2026-10-17 00:00', 24, fn ($h) => 100 + 10 * $h);
        $this->hours($moisture, '2026-10-17 00:00', 24, fn ($h) => 20 + 0.5 * $h);   // exactly linear: r = 1

        $r = $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'free', 'params' => [$flow->id, $moisture->id], 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
        ]))->assertOk()->json();

        $this->assertFalse($r['comparable']);                                 // L and %: no difference computed
        $this->assertSame(['L', '%'], $r['units']);
        $this->assertNull($r['series'][1]['comparison']);
        $this->assertEquals(1, $r['correlation']['r']);
        $this->assertEquals(0.05, $r['correlation']['slope']);
        $this->assertSame(24, $r['correlation']['n']);
        $this->assertStringContainsString('UTG – Daily flow and UTG – Moisture: strong positive relationship (r = 1, 24 common hours)', implode(' ', $r['findings']));

        $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'free', 'params' => [$flow->id], 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
        ]))->assertStatus(422);
    }

    public function test_percent_differences_are_in_points_and_the_report_is_named_after_its_content(): void
    {
        $ref = $this->param('irrigation', ['name' => 'Ref', 'metric' => 'soil_moisture', 'zone' => Zones::REFERENCE, 'unit' => '%']);
        $apv = $this->param('irrigation', ['name' => 'Apv', 'metric' => 'soil_moisture', 'zone' => Zones::APV, 'unit' => '%']);
        $this->hours($ref, '2026-10-17 00:00', 24, fn () => 18.9);
        $this->hours($apv, '2026-10-17 00:00', 24, fn () => 20.3);
        $q = ['mode' => 'site', 'site' => $this->site->id, 'quantity' => 'soil_moisture', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17'];

        $r = $this->actingAs($this->admin)->getJson(route('analysis.data', $q))->assertOk()->json();
        $this->assertStringContainsString('APV field averages 20.3 % against 18.9 % at Reference: +1.4 points, over 24 common hours.', $r['findings'][0]);

        $this->actingAs($this->admin)->get(route('analysis.report', $q))->assertOk()
            ->assertSee('<title>APV-MaGa_soil-moisture_utg-zones_2026-10-17_2026-10-17</title>', false);
    }

    public function test_an_administrator_sets_the_zone_of_a_parameter(): void
    {
        $temp = $this->param('weather', ['name' => 'Temp', 'metric' => 'air_temperature', 'unit' => '°C', 'zone' => null]);
        $url  = route('admin.parameters.update', [$this->site, $temp->category, $temp]);

        $this->actingAs($this->admin)->put($url, ['name' => 'Temp', 'data_type' => 'float', 'metric' => 'air_temperature', 'zone' => 'under_pv'])
            ->assertRedirect();
        $this->assertSame('under_pv', $temp->refresh()->zone);

        // A request without the field keeps it; an unknown zone is refused.
        $this->actingAs($this->admin)->put($url, ['name' => 'Temp', 'data_type' => 'float', 'metric' => 'air_temperature'])->assertRedirect();
        $this->assertSame('under_pv', $temp->refresh()->zone);
        $this->actingAs($this->admin)->put($url, ['name' => 'Temp', 'data_type' => 'float', 'zone' => 'moon'])->assertSessionHasErrors('zone');

        // Created with a zone from the add form.
        $this->actingAs($this->admin)->post(route('admin.parameters.store', [$this->site, $temp->category]), [
            'name' => 'Edge temperature', 'data_type' => 'float', 'metric' => 'air_temperature', 'zone' => 'module_edge', 'input_type' => 'sensor',
        ])->assertRedirect();
        $this->assertSame('module_edge', SiteParameter::where('name', 'Edge temperature')->value('zone'));
    }

    public function test_free_comparison_only_reads_parameters_of_sites_the_user_may_open(): void
    {
        $mine = $this->param('weather', ['name' => 'Temp', 'metric' => 'air_temperature', 'zone' => Zones::REFERENCE, 'unit' => '°C']);
        $otherSite = Site::factory()->create(['status' => 'active']);
        $foreign = SiteParameter::factory()->create([
            'site_category_id' => SiteCategory::factory()->create(['site_id' => $otherSite->id, 'is_active' => true])->id,
            'is_active' => true, 'data_type' => 'float', 'metric' => 'air_temperature',
        ]);
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);
        $this->site->users()->attach($observer->id, ['role' => User::ROLE_OBSERVER]);

        $this->actingAs($observer)->getJson(route('analysis.data', [
            'mode' => 'free', 'params' => [$mine->id, $foreign->id], 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
        ]))->assertForbidden();

        $this->actingAs($observer)->get(route('analysis'))->assertOk()->assertDontSee('"id":' . $foreign->id . ',', false);
    }

    public function test_any_parameters_can_take_the_mean_of_a_whole_zone(): void
    {
        // Two soil moisture sensors in the APV field, one in the reference field, and rain.
        $apv1 = $this->param('irrigation', ['name' => 'APV 1 – Moisture', 'metric' => 'soil_moisture', 'zone' => Zones::APV, 'unit' => '%']);
        $apv2 = $this->param('irrigation', ['name' => 'APV 2 – Moisture', 'metric' => 'soil_moisture', 'zone' => Zones::APV, 'unit' => '%']);
        $ref  = $this->param('irrigation', ['name' => 'Ref 1 – Moisture', 'metric' => 'soil_moisture', 'zone' => Zones::REFERENCE, 'unit' => '%']);
        $rain = $this->param('weather', ['name' => 'Rain', 'metric' => 'rain', 'unit' => 'mm']);
        $this->hours($apv1, '2026-10-17 00:00', 24, fn () => 30);
        $this->hours($apv2, '2026-10-17 00:00', 24, fn () => 40);
        $this->hours($ref, '2026-10-17 00:00', 24, fn () => 20);
        $this->hours($rain, '2026-10-17 00:00', 24, fn ($h) => $h % 2);

        // Offered on the page only where a zone has two or more sensors.
        $page = $this->actingAs($this->admin)->get(route('analysis'))->assertOk();
        $page->assertSee("zone:{$this->site->id}:soil_moisture:apv", false)
            ->assertSee('APV field (mean of 2 sensors)', false)
            ->assertDontSee("zone:{$this->site->id}:soil_moisture:reference", false);

        $r = $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'free', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
            'params' => ["zone:{$this->site->id}:soil_moisture:apv", "zone:{$this->site->id}:soil_moisture:reference", (string) $rain->id],
        ]))->assertOk()->json();

        $this->assertSame([
            'UTG – Soil moisture, APV field (mean)',
            'UTG – Soil moisture, Reference (mean)',
            'UTG – Rain',
        ], array_column($r['series'], 'label'));
        $this->assertSame([2, 1, 1], array_column($r['series'], 'sensors'));
        $this->assertEquals(35, $r['series'][0]['stats']['mean']);   // the mean of both APV sensors
        $this->assertEquals(20, $r['series'][1]['stats']['mean']);
    }

    public function test_a_zone_mean_of_a_site_the_user_may_not_open_is_refused(): void
    {
        $otherSite = Site::factory()->create(['status' => 'active']);
        $observer  = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);
        $this->site->users()->attach($observer->id, ['role' => User::ROLE_OBSERVER]);
        $mine = $this->param('weather', ['name' => 'Temp', 'metric' => 'air_temperature', 'zone' => Zones::REFERENCE, 'unit' => '°C']);

        $this->actingAs($observer)->getJson(route('analysis.data', [
            'mode' => 'free', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
            'params' => [(string) $mine->id, "zone:{$otherSite->id}:air_temperature:reference"],
        ]))->assertForbidden();

        $this->actingAs($this->admin)->getJson(route('analysis.data', [
            'mode' => 'free', 'step' => 'hour', 'from' => '2026-10-17', 'to' => '2026-10-17',
            'params' => [(string) $mine->id, 'zone:1:../../etc:apv'],
        ]))->assertStatus(422);
    }
}
