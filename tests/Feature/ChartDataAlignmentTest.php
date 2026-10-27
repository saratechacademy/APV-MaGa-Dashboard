<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * chart-data merges each parameter's readings onto a shared timestamp axis
 * (the union of every timestamp seen across all series) rather than zipping
 * arrays by index. This is a regression test for exactly the bug the code
 * comment describes: two series with different report timestamps used to
 * silently plot series B's values under series A's timestamps.
 */
class ChartDataAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_series_with_different_timestamps_are_aligned_on_a_shared_axis_with_nulls_for_gaps(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);

        $paramA = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'param_a']);
        $paramB = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'param_b']);

        // Param A reports at 10:00, 10:05, 10:10 — Param B only at 10:00 and
        // 10:10 (it skipped the 10:05 tick). A naive index-based zip would
        // put B's 10:10 value under A's 10:05 label.
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramA->id, 'value' => 1, 'read_at' => '2026-06-01 10:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramA->id, 'value' => 2, 'read_at' => '2026-06-01 10:05:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramA->id, 'value' => 3, 'read_at' => '2026-06-01 10:10:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramB->id, 'value' => 100, 'read_at' => '2026-06-01 10:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramB->id, 'value' => 300, 'read_at' => '2026-06-01 10:10:00']);

        $response = $this->actingAs($admin)->getJson(
            route('dashboard.chart-data', [$site, 'solar']) . '?from=2026-06-01&to=2026-06-02'
        );

        $response->assertOk();
        $json = $response->json();

        $this->assertCount(3, $json['labels']); // union of all timestamps: 10:00, 10:05, 10:10
        // One epoch per label, so the page can tell a 5-minute step from a 2-day silence.
        $this->assertSame([300, 300], [$json['timestamps'][1] - $json['timestamps'][0], $json['timestamps'][2] - $json['timestamps'][1]]);

        $datasetA = collect($json['datasets'])->firstWhere('slug', 'param_a');
        $datasetB = collect($json['datasets'])->firstWhere('slug', 'param_b');

        $this->assertEquals([1, 2, 3], $datasetA['data']);
        // B has no reading at the middle timestamp — must be null, not 2's
        // value (100) shifted into the wrong slot, and not silently dropped.
        $this->assertEquals([100, null, 300], $datasetB['data']);
    }

    public function test_a_long_silence_takes_room_on_the_axis_instead_of_vanishing(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'param_a']);

        // Every 20 minutes, then nothing for two days, then again.
        foreach (['2026-06-01 10:00', '2026-06-01 10:20', '2026-06-01 10:40', '2026-06-03 10:40', '2026-06-03 11:00'] as $at) {
            SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 5, 'read_at' => $at . ':00']);
        }

        $json = $this->actingAs($admin)
            ->getJson(route('dashboard.chart-data', [$site, 'solar']) . '?from=2026-06-01&to=2026-06-03')
            ->assertOk()->json();

        $data = $json['datasets'][0]['data'];

        // The five readings are all there, in order, with empty slots between the 3rd and 4th.
        $this->assertSame([5, 5, 5, 5, 5], array_values(array_filter($data, fn ($v) => $v !== null)));
        $this->assertSame([5, 5, 5], array_slice($data, 0, 3));
        $this->assertNull($data[3]);
        $this->assertGreaterThan(20, count($data));
        $this->assertLessThanOrEqual(5 + 40, count($data));
        $this->assertCount(count($data), $json['labels']);
        $this->assertCount(count($data), $json['timestamps']);
    }

    public function test_an_empty_window_reports_when_the_last_reading_was(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'param_a']);

        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => '2026-06-01 10:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 2, 'read_at' => '2026-06-01 10:05:00']);

        $url = route('dashboard.chart-data', [$site, 'solar']);

        // The sensor went silent on 1 June: a later window is empty, but says so.
        $empty = $this->actingAs($admin)->getJson($url . '?from=2026-07-01&to=2026-07-02')->assertOk()->json();
        $this->assertSame([], $empty['datasets']);
        $this->assertStringStartsWith('2026-06-01T10:05:00', $empty['last_reading_at']);

        // A window with data doesn't need it.
        $full = $this->actingAs($admin)->getJson($url . '?from=2026-06-01&to=2026-06-02')->assertOk()->json();
        $this->assertNull($full['last_reading_at']);
    }

    public function test_manual_only_parameter_appears_alongside_sensor_data_on_the_same_axis(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'agriculture']);

        $sensorParam = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'soil_moisture', 'input_type' => 'sensor']);
        $manualParam = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'crop_yield', 'input_type' => 'manual', 'data_type' => 'float']);

        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $sensorParam->id, 'value' => 42, 'read_at' => '2026-06-01 08:00:00']);

        $user = User::factory()->create();
        \App\Models\ManualReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $manualParam->id, 'user_id' => $user->id,
            'value' => 7, 'reading_date' => '2026-06-01 09:00:00',
        ]);

        $response = $this->actingAs($admin)->getJson(
            route('dashboard.chart-data', [$site, 'agriculture']) . '?from=2026-06-01&to=2026-06-02'
        );

        $response->assertOk();
        $slugs = collect($response->json('datasets'))->pluck('slug');
        $this->assertTrue($slugs->contains('soil_moisture'));
        $this->assertTrue($slugs->contains('crop_yield'));
    }

    public function test_readings_outside_the_requested_range_are_excluded(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'solar_output']);

        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 111.1, 'read_at' => '2026-07-03 12:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 222.2, 'read_at' => '2026-07-10 12:00:00']);

        $response = $this->actingAs($admin)->getJson(
            route('dashboard.chart-data', [$site, 'solar']) . '?from=2026-07-01&to=2026-07-05'
        );

        $dataset = collect($response->json('datasets'))->firstWhere('slug', 'solar_output');
        $this->assertContains(111.1, $dataset['data']);
        $this->assertNotContains(222.2, $dataset['data']);
    }

    public function test_a_long_window_is_averaged_into_buckets_so_the_chart_stays_small(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'irrigation']);
        $params   = SiteParameter::factory()->count(10)->create(['site_category_id' => $category->id, 'input_type' => 'sensor']);

        // A month of readings every 20 minutes, each device at its own second:
        // read raw, that is one axis slot per reading — over 20,000.
        $rows = [];
        $start = strtotime('2026-06-01 00:00:00');
        foreach ($params as $i => $param) {
            for ($t = $start + $i; $t < $start + 30 * 86400; $t += 1200) {
                $rows[] = ['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => $i, 'read_at' => date('Y-m-d H:i:s', $t)];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            SensorReading::insert($chunk);
        }

        $json = $this->actingAs($admin)->getJson(
            route('dashboard.chart-data', [$site, 'irrigation']) . '?from=2026-06-01&to=2026-06-30'
        )->assertOk()->json();

        $this->assertSame(120, $json['bucket_minutes']);
        $this->assertLessThanOrEqual(600, count($json['labels']));
        // Every device lands in the same buckets, with its own average.
        $dataset = collect($json['datasets'])->firstWhere('slug', $params[3]->slug);
        $this->assertSame([3], array_values(array_unique(array_filter($dataset['data'], fn ($v) => $v !== null))));
        $this->assertNotContains(null, $dataset['data']);
    }

    public function test_up_to_two_days_the_readings_are_not_averaged(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'slug' => 'output']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => '2026-06-01 10:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 3, 'read_at' => '2026-06-01 10:01:00']);

        $json = $this->actingAs($admin)->getJson(
            route('dashboard.chart-data', [$site, 'solar']) . '?from=2026-06-01&to=2026-06-02'
        )->assertOk()->json();

        $this->assertSame(0, $json['bucket_minutes']);
        $this->assertEquals([1, 3], $json['datasets'][0]['data']);
    }
}
