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

        $datasetA = collect($json['datasets'])->firstWhere('slug', 'param_a');
        $datasetB = collect($json['datasets'])->firstWhere('slug', 'param_b');

        $this->assertEquals([1, 2, 3], $datasetA['data']);
        // B has no reading at the middle timestamp — must be null, not 2's
        // value (100) shifted into the wrong slot, and not silently dropped.
        $this->assertEquals([100, null, 300], $datasetB['data']);
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
}
