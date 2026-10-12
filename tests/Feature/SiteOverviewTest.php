<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The All Sites overview is the first page every user lands on. It used to
 * plot "the first parameter of the Solar category" under a fixed "Solar
 * Output (kW)" title and look KPIs up by default-template slugs — wrong
 * numbers under wrong units for any site built differently. It is now built
 * from parameter metrics; these tests pin that down.
 */
class SiteOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 12:00:00');
    }

    private function param(Site $site, string $category, string $metric, array $readings, array $attrs = []): SiteParameter
    {
        $cat = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => $category],
            ['name' => ucfirst($category), 'is_active' => true, 'offline_threshold_minutes' => 60, 'sort_order' => 0],
        );

        $param = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'metric' => $metric] + $attrs);

        foreach ($readings as $at => $value) {
            SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => $value, 'read_at' => $at]);
        }

        return $param;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_each_site_card_shows_the_metrics_that_site_actually_has(): void
    {
        $utg  = Site::factory()->create(['name' => 'UTG', 'status' => 'active']);
        $fass = Site::factory()->create(['name' => 'Fass', 'status' => 'active']);

        // Two air-temperature sensors are averaged; PAR is taken from the reference sensor only.
        $this->param($utg, 'weather', 'air_temperature', ['2026-10-13 11:50:00' => 30]);
        $this->param($utg, 'weather', 'air_temperature', ['2026-10-13 11:50:00' => 32]);
        $this->param($utg, 'solar', 'par_reference', ['2026-10-13 11:50:00' => 1500]);
        $this->param($utg, 'solar', 'par_under_pv', ['2026-10-13 11:50:00' => 200]);
        // Silent for a month: shown, but flagged with its age instead of passing for current.
        $this->param($utg, 'irrigation', 'soil_moisture', ['2026-09-10 13:00:00' => 20.4]);

        $this->param($fass, 'weather', 'wind_speed', ['2026-10-13 11:50:00' => 2.5]);

        $response = $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();

        $cards = $response->viewData('cards');

        $this->assertSame(
            [['air_temperature', 31.0, false], ['par_reference', 1500.0, false], ['soil_moisture', 20.4, true]],
            array_map(fn ($t) => [$t['metric'], round($t['value'], 1), $t['stale']], $cards[$utg->id]['tiles']),
        );
        $this->assertSame(['wind_speed'], array_column($cards[$fass->id]['tiles'], 'metric'));

        $this->assertSame('par_reference', $cards[$utg->id]['sparkline']);
        $this->assertNull($cards[$fass->id]['sparkline']);

        // Charts exist only for metrics somebody has, titled with their own unit.
        $this->assertSame(['par_reference', 'air_temperature', 'soil_moisture', 'wind_speed'], $response->viewData('chartMetrics'));
        $response->assertSee('PAR (reference) (µmol/m²/s)')
            ->assertSee('Soil moisture (%)')
            ->assertDontSee('Solar Output (kW)')
            ->assertDontSee('Borehole');
    }

    public function test_status_follows_thresholds_on_fresh_readings_only(): void
    {
        $hot   = Site::factory()->create(['status' => 'active']);
        $stale = Site::factory()->create(['status' => 'active']);
        $limits = ['critical_threshold' => 45, 'threshold_direction' => 'above'];

        $this->param($hot, 'weather', 'air_temperature', ['2026-10-13 11:50:00' => 50], $limits);
        $this->param($stale, 'weather', 'air_temperature', ['2026-09-10 13:00:00' => 50], $limits);

        $cards = $this->actingAs($this->admin())->get(route('dashboard'))->viewData('cards');

        $this->assertSame('critical', $cards[$hot->id]['status']);
        $this->assertSame('normal', $cards[$stale->id]['status']);
    }

    public function test_overview_data_puts_every_site_on_one_time_axis(): void
    {
        $a = Site::factory()->create(['name' => 'A', 'status' => 'active']);
        $b = Site::factory()->create(['name' => 'B', 'status' => 'active']);

        // A reports on the 10-minute mark from two sensors; B at odd times, once.
        $this->param($a, 'weather', 'air_temperature', ['2026-10-13 10:00:00' => 20, '2026-10-13 10:10:00' => 22]);
        $this->param($a, 'weather', 'air_temperature', ['2026-10-13 10:00:00' => 24]);
        $this->param($b, 'weather', 'air_temperature', ['2026-10-13 10:13:27' => 30]);

        $json = $this->actingAs($this->admin())
            ->getJson(route('dashboard.overview-data') . '?hours=6')
            ->assertOk()->json();

        // 6 hours → 5-minute buckets, same labels for every site.
        $at = fn (string $label) => array_search($label, $json['labels'], true);
        $series = collect($json['series']['air_temperature'])->keyBy('name');

        $this->assertCount(count($json['labels']), $series['A']['data']);
        $this->assertCount(count($json['labels']), $series['B']['data']);

        $this->assertEquals(22, $series['A']['data'][$at('10:00')]);   // mean of 20 and 24
        $this->assertEquals(22, $series['A']['data'][$at('10:10')]);
        $this->assertNull($series['A']['data'][$at('10:05')]);
        $this->assertEquals(30, $series['B']['data'][$at('10:10')]);   // 10:13:27 falls in the 10:10 bucket
        $this->assertNull($series['B']['data'][$at('10:00')]);
    }

    public function test_overview_data_only_covers_sites_the_user_may_see(): void
    {
        $mine  = Site::factory()->create(['name' => 'Mine', 'status' => 'active']);
        $other = Site::factory()->create(['name' => 'Other', 'status' => 'active']);
        $this->param($mine, 'weather', 'air_temperature', ['2026-10-13 11:00:00' => 20]);
        $this->param($other, 'weather', 'air_temperature', ['2026-10-13 11:00:00' => 40]);

        $observer = User::factory()->create(['role' => 'observateur', 'status' => 'active']);
        $observer->assignedSites()->attach($mine->id, ['role' => 'observateur']);

        $json = $this->actingAs($observer)->getJson(route('dashboard.overview-data'))->assertOk()->json();

        $this->assertSame(['Mine'], array_column($json['series']['air_temperature'], 'name'));

        $this->get(route('dashboard'))->assertOk()->assertSee('Mine')->assertDontSee('Other');
    }
}
