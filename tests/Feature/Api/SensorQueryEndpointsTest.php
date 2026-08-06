<?php

namespace Tests\Feature\Api;

use App\Models\ActuatorCommand;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rigorous coverage of the 5 read-only IoT API endpoints (latest, status,
 * commands, history, stats) — SensorIngestionTest already covers store() in
 * depth. These were only ever spot-checked manually before; this locks down
 * every branch (auth, filtering, pagination, aggregation, edge cases with no
 * data) so a firmware integrator relying on this API gets a guaranteed
 * contract, not "worked when I tried it once."
 */
class SensorQueryEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(): Site
    {
        return Site::factory()->create(['status' => 'active']);
    }

    private function makeCategory(Site $site, string $slug = 'solar', bool $active = true): SiteCategory
    {
        return SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => $slug, 'is_active' => $active]);
    }

    private function makeParam(SiteCategory $category, array $attrs = []): SiteParameter
    {
        return SiteParameter::factory()->create(array_merge([
            'site_category_id' => $category->id, 'input_type' => 'sensor', 'is_active' => true,
        ], $attrs));
    }

    // ─────────────────────────── shared auth ───────────────────────────

    public function test_all_five_endpoints_require_a_valid_api_key(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        $endpoints = [
            "/api/sensors/{$site->slug}/solar/latest",
            "/api/sensors/{$site->slug}/status",
            "/api/commands/{$site->slug}/solar",
            "/api/sensors/{$site->slug}/solar/history",
            "/api/sensors/{$site->slug}/solar/stats",
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertStatus(401);
            $this->getJson($url, ['X-API-Key' => 'wrong-key'])->assertStatus(401);
        }
    }

    // ────────────────────────────── latest ──────────────────────────────

    public function test_latest_returns_every_active_parameter_even_without_readings(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $withData = $this->makeParam($category, ['slug' => 'has_data']);
        $noData   = $this->makeParam($category, ['slug' => 'no_data']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $withData->id, 'value' => 12.3, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/latest", ['X-API-Key' => $site->api_key]);

        $response->assertOk();
        $this->assertEquals(12.3, $response->json('data.has_data.value'));
        $this->assertNotNull($response->json('data.has_data.read_at'));
        $this->assertNull($response->json('data.no_data.value'));
        $this->assertNull($response->json('data.no_data.read_at'));
    }

    public function test_latest_excludes_inactive_parameters(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'active_one', 'is_active' => true]);
        $this->makeParam($category, ['slug' => 'inactive_one', 'is_active' => false]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/latest", ['X-API-Key' => $site->api_key]);

        $this->assertArrayHasKey('active_one', $response->json('data'));
        $this->assertArrayNotHasKey('inactive_one', $response->json('data'));
    }

    public function test_latest_falls_back_to_text_value_when_numeric_value_is_absent(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'status_code', 'data_type' => 'string']);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => null, 'value_text' => 'STANDBY', 'read_at' => now(),
        ]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/latest", ['X-API-Key' => $site->api_key]);

        $this->assertSame('STANDBY', $response->json('data.status_code.value'));
    }

    public function test_latest_on_unknown_category_returns_404(): void
    {
        $site = $this->makeSite();

        $this->getJson("/api/sensors/{$site->slug}/does-not-exist/latest", ['X-API-Key' => $site->api_key])
            ->assertStatus(404);
    }

    public function test_latest_on_inactive_category_is_treated_as_not_found(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site, 'solar', active: false);

        $this->getJson("/api/sensors/{$site->slug}/solar/latest", ['X-API-Key' => $site->api_key])
            ->assertStatus(404);
    }

    // ────────────────────────────── status ──────────────────────────────

    public function test_status_reports_one_entry_per_active_category(): void
    {
        $site  = $this->makeSite();
        $solar = $this->makeCategory($site, 'solar');
        $water = $this->makeCategory($site, 'water');
        $this->makeCategory($site, 'weather', active: false); // must not appear

        $param = $this->makeParam($solar);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/status", ['X-API-Key' => $site->api_key]);

        $status = $response->json('status');
        $this->assertArrayHasKey('solar', $status);
        $this->assertArrayHasKey('water', $status);
        $this->assertArrayNotHasKey('weather', $status);
        $this->assertNotNull($status['solar']['read_at']);
        $this->assertNull($status['water']['read_at']); // no readings at all for water
    }

    public function test_status_last_reading_is_a_human_readable_diff(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id, 'read_at' => now()->subHours(2),
        ]);

        $response = $this->getJson("/api/sensors/{$site->slug}/status", ['X-API-Key' => $site->api_key]);

        $this->assertStringContainsString('hour', $response->json('status.solar.last_reading'));
    }

    // ───────────────────────────── commands ─────────────────────────────

    public function test_commands_only_lists_controllable_parameters(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'readonly_switch', 'data_type' => 'switch', 'control_type' => 'readonly']);
        $this->makeParam($category, ['slug' => 'valve', 'data_type' => 'switch', 'control_type' => 'controllable']);
        $this->makeParam($category, ['slug' => 'sensor_value', 'data_type' => 'float', 'control_type' => 'readonly']);

        $response = $this->getJson("/api/commands/{$site->slug}/solar", ['X-API-Key' => $site->api_key]);

        $commands = $response->json('commands');
        $this->assertArrayHasKey('valve', $commands);
        $this->assertArrayNotHasKey('readonly_switch', $commands);
        $this->assertArrayNotHasKey('sensor_value', $commands);
    }

    public function test_commands_defaults_to_desired_state_zero_when_none_has_ever_been_issued(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'valve', 'data_type' => 'switch', 'control_type' => 'controllable']);

        $response = $this->getJson("/api/commands/{$site->slug}/solar", ['X-API-Key' => $site->api_key]);

        $this->assertSame(0, $response->json('commands.valve.desired_state'));
        $this->assertNull($response->json('commands.valve.reported_state'));
    }

    public function test_commands_reflects_a_pending_desired_state_from_the_dashboard(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'valve', 'data_type' => 'switch', 'control_type' => 'controllable']);
        ActuatorCommand::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'desired_state' => 1, 'reported_state' => 0,
        ]);

        $response = $this->getJson("/api/commands/{$site->slug}/solar", ['X-API-Key' => $site->api_key]);

        $this->assertSame(1, $response->json('commands.valve.desired_state'));
        $this->assertSame(0, $response->json('commands.valve.reported_state'));
    }

    // ────────────────────────────── history ─────────────────────────────

    public function test_history_defaults_to_the_last_day_when_no_range_is_given(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => now()->subHours(12)]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 2, 'read_at' => now()->subDays(5)]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/history", ['X-API-Key' => $site->api_key]);

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
    }

    public function test_history_respects_an_explicit_date_range(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 111.1, 'read_at' => '2026-07-03 12:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 222.2, 'read_at' => '2026-07-10 12:00:00']);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/history?from=2026-07-01&to=2026-07-05",
            ['X-API-Key' => $site->api_key]
        );

        $this->assertSame(1, $response->json('total'));
        $this->assertSame(111.1, $response->json('data.0.value'));
        $this->assertSame('solar_output', $response->json('data.0.parameter'));
    }

    public function test_history_range_over_90_days_is_rejected(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/history?from=2026-01-01&to=2026-06-01",
            ['X-API-Key' => $site->api_key]
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('90 days', $response->json('error'));
    }

    public function test_history_invalid_date_format_returns_a_structured_422(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/history?from=not-a-date",
            ['X-API-Key' => $site->api_key]
        );

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_history_params_filter_narrows_to_the_requested_slugs_only(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $paramA = $this->makeParam($category, ['slug' => 'param_a']);
        $paramB = $this->makeParam($category, ['slug' => 'param_b']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramA->id, 'read_at' => now()]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramB->id, 'read_at' => now()]);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/history?params=param_a",
            ['X-API-Key' => $site->api_key]
        );

        $this->assertSame(1, $response->json('total'));
        $this->assertSame('param_a', $response->json('data.0.parameter'));
    }

    public function test_history_excludes_manual_input_parameters(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $sensorParam = $this->makeParam($category, ['slug' => 'sensor_one', 'input_type' => 'sensor']);
        // A manual parameter has no SensorReading rows by construction (manual
        // data lives in manual_readings), but this also confirms the query
        // itself is scoped to input_type=sensor, not just "no data happens to
        // exist" — sneak a sensor_readings row in under a manual param's id
        // to prove it gets excluded even if data existed there.
        $manualParam = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'input_type' => 'manual', 'slug' => 'manual_one',
        ]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $sensorParam->id, 'read_at' => now()]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $manualParam->id, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/history", ['X-API-Key' => $site->api_key]);

        $this->assertSame(1, $response->json('total'));
        $this->assertSame('sensor_one', $response->json('data.0.parameter'));
    }

    public function test_history_pagination_splits_results_correctly(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category);
        for ($i = 0; $i < 5; $i++) {
            SensorReading::factory()->create([
                'site_id' => $site->id, 'site_parameter_id' => $param->id,
                'value' => $i, 'read_at' => now()->subMinutes($i),
            ]);
        }

        $page1 = $this->getJson("/api/sensors/{$site->slug}/solar/history?per_page=2&page=1", ['X-API-Key' => $site->api_key]);
        $page2 = $this->getJson("/api/sensors/{$site->slug}/solar/history?per_page=2&page=2", ['X-API-Key' => $site->api_key]);
        $page3 = $this->getJson("/api/sensors/{$site->slug}/solar/history?per_page=2&page=3", ['X-API-Key' => $site->api_key]);

        $this->assertSame(5, $page1->json('total'));
        $this->assertSame(3, $page1->json('total_pages'));
        $this->assertCount(2, $page1->json('data'));
        $this->assertCount(2, $page2->json('data'));
        $this->assertCount(1, $page3->json('data')); // remainder on the last page

        // No overlap/duplication across pages.
        $allTimestamps = array_merge(
            array_column($page1->json('data'), 'read_at'),
            array_column($page2->json('data'), 'read_at'),
            array_column($page3->json('data'), 'read_at'),
        );
        $this->assertCount(5, array_unique($allTimestamps));
    }

    public function test_history_page_beyond_available_data_returns_an_empty_but_valid_response(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/history?per_page=10&page=99", ['X-API-Key' => $site->api_key]);

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertSame([], $response->json('data'));
    }

    public function test_history_per_page_over_5000_is_rejected(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        $this->getJson("/api/sensors/{$site->slug}/solar/history?per_page=10000", ['X-API-Key' => $site->api_key])
            ->assertStatus(422);
    }

    public function test_history_from_and_to_set_to_the_same_calendar_day_covers_the_whole_day_not_a_zero_width_window(): void
    {
        // Regression: from=X&to=X used to Carbon::parse() both to midnight,
        // producing a zero-second window that silently returned no data even
        // though "give me that day" is the most natural way to call this.
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => 42, 'read_at' => '2026-06-15 14:30:00',
        ]);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/history?from=2026-06-15&to=2026-06-15",
            ['X-API-Key' => $site->api_key]
        );

        $this->assertSame(1, $response->json('total'));
    }

    // ─────────────────────────────── stats ───────────────────────────────

    public function test_stats_defaults_to_daily_buckets_over_the_last_30_days(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['data_type' => 'float']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 10, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/stats", ['X-API-Key' => $site->api_key]);

        $response->assertOk()->assertJson(['period' => 'day']);
    }

    public function test_stats_aggregates_avg_min_max_sum_count_correctly_within_a_bucket(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'data_type' => 'float']);

        foreach ([10, 20, 30] as $value) {
            SensorReading::factory()->create([
                'site_id' => $site->id, 'site_parameter_id' => $param->id,
                'value' => $value, 'read_at' => '2026-06-15 10:00:00',
            ]);
        }

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/stats?period=day&from=2026-06-01&to=2026-06-30",
            ['X-API-Key' => $site->api_key]
        );

        $bucket = $response->json('stats.solar_output.series.0');
        $this->assertSame(3, $bucket['count']);
        $this->assertEquals(20, $bucket['avg']);
        $this->assertEquals(10, $bucket['min']);
        $this->assertEquals(30, $bucket['max']);
        $this->assertEquals(60, $bucket['sum']);
    }

    public function test_stats_separates_readings_into_different_buckets_by_day(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'data_type' => 'float']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 5, 'read_at' => '2026-06-15 08:00:00']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 9, 'read_at' => '2026-06-16 08:00:00']);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/stats?period=day&from=2026-06-01&to=2026-06-30",
            ['X-API-Key' => $site->api_key]
        );

        $series = $response->json('stats.solar_output.series');
        $this->assertCount(2, $series);
    }

    public function test_stats_excludes_string_and_boolean_data_types(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'text_param', 'data_type' => 'string']);
        $this->makeParam($category, ['slug' => 'bool_param', 'data_type' => 'boolean']);
        $this->makeParam($category, ['slug' => 'numeric_param', 'data_type' => 'float']);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/stats", ['X-API-Key' => $site->api_key]);

        $stats = $response->json('stats');
        $this->assertArrayHasKey('numeric_param', $stats);
        $this->assertArrayNotHasKey('text_param', $stats);
        $this->assertArrayNotHasKey('bool_param', $stats);
    }

    public function test_stats_date_range_too_large_for_period_is_rejected(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        // "hour" period caps at 7 days.
        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/stats?period=hour&from=2026-01-01&to=2026-02-01",
            ['X-API-Key' => $site->api_key]
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('period=hour', $response->json('error'));
    }

    public function test_stats_params_filter_narrows_to_the_requested_slugs_only(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $paramA = $this->makeParam($category, ['slug' => 'param_a', 'data_type' => 'float']);
        $paramB = $this->makeParam($category, ['slug' => 'param_b', 'data_type' => 'float']);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramA->id, 'value' => 1, 'read_at' => now()]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $paramB->id, 'value' => 2, 'read_at' => now()]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/stats?params=param_a", ['X-API-Key' => $site->api_key]);

        $stats = $response->json('stats');
        $this->assertArrayHasKey('param_a', $stats);
        $this->assertArrayNotHasKey('param_b', $stats);
    }

    public function test_stats_from_and_to_set_to_the_same_calendar_day_covers_the_whole_day_not_a_zero_width_window(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'data_type' => 'float']);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => 42, 'read_at' => '2026-06-15 14:30:00',
        ]);

        $response = $this->getJson(
            "/api/sensors/{$site->slug}/solar/stats?from=2026-06-15&to=2026-06-15",
            ['X-API-Key' => $site->api_key]
        );

        $this->assertNotEmpty($response->json('stats.solar_output.series'));
    }

    public function test_stats_ignores_readings_with_a_null_value(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'data_type' => 'float']);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => null, 'value_text' => 'ERR', 'read_at' => now(),
        ]);

        $response = $this->getJson("/api/sensors/{$site->slug}/solar/stats", ['X-API-Key' => $site->api_key]);

        $this->assertSame([], $response->json('stats.solar_output.series'));
    }
}
