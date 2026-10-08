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
 * ApiController::store() is the only entry point for real ESP32 sensor data —
 * a regression here fails silently from the operator's point of view (no UI
 * error, just missing/wrong data points on a chart days later).
 */
class SensorIngestionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(): Site
    {
        return Site::factory()->create(['status' => 'active']);
    }

    private function makeCategory(Site $site, string $slug = 'solar'): SiteCategory
    {
        return SiteCategory::factory()->create([
            'site_id' => $site->id, 'slug' => $slug, 'is_active' => true,
        ]);
    }

    private function makeParam(SiteCategory $category, array $attrs = []): SiteParameter
    {
        return SiteParameter::factory()->create(array_merge([
            'site_category_id' => $category->id,
            'input_type'       => 'sensor',
            'is_active'        => true,
        ], $attrs));
    }

    public function test_invalid_api_key_is_rejected(): void
    {
        $site = $this->makeSite();

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", ['x' => 1], [
            'X-API-Key' => 'apv-totally-wrong-key',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'error' => 'Invalid API key.']);
    }

    public function test_api_key_in_the_url_is_not_accepted(): void
    {
        $site = $this->makeSite();
        $cat  = $this->makeCategory($site);
        $this->makeParam($cat, ['slug' => 'solar_output']);

        $this->postJson("/api/sensors/{$site->slug}/solar?api_key={$site->api_key}", ['solar_output' => 4.2])
            ->assertStatus(401);

        $this->assertSame(0, SensorReading::count());
    }

    public function test_missing_api_key_is_rejected(): void
    {
        $site = $this->makeSite();

        $this->postJson("/api/sensors/{$site->slug}/solar", ['x' => 1])
            ->assertStatus(401);
    }

    public function test_unknown_site_returns_404_before_checking_the_key(): void
    {
        $this->postJson('/api/sensors/does-not-exist/solar', ['x' => 1])
            ->assertStatus(404)
            ->assertJson(['success' => false, 'error' => 'Site not found.']);
    }

    public function test_inactive_site_is_treated_as_not_found(): void
    {
        $site = Site::factory()->create();
        $site->status = 'inactive';
        $site->save();

        $this->postJson("/api/sensors/{$site->slug}/solar", ['x' => 1], [
            'X-API-Key' => $site->api_key,
        ])->assertStatus(404);
    }

    public function test_unknown_category_returns_404_with_available_categories(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site, 'solar');

        $response = $this->postJson("/api/sensors/{$site->slug}/irrigation", ['x' => 1], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(404)->assertJsonFragment(['available_categories' => ['solar']]);
    }

    public function test_unknown_parameter_slug_is_reported_but_does_not_fail_the_whole_request(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'solar_output']);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [
            'solar_output' => 4.2,
            'made_up_slug' => 99,
        ], ['X-API-Key' => $site->api_key]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertStringContainsString("'made_up_slug'", $response->json('errors')[0]);
        $this->assertDatabaseHas('sensor_readings', ['value' => 4.2]);
        $this->assertDatabaseMissing('sensor_readings', ['value' => 99]);
    }

    public function test_numeric_value_is_stored_in_value_column(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'data_type' => 'float']);

        $this->postJson("/api/sensors/{$site->slug}/solar", ['solar_output' => 4.2], [
            'X-API-Key' => $site->api_key,
        ])->assertStatus(201);

        $reading = SensorReading::where('site_parameter_id', $param->id)->first();
        $this->assertSame(4.2, $reading->value);
        $this->assertNull($reading->value_text);
    }

    public function test_non_numeric_value_is_stored_in_value_text_column(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'status_code', 'data_type' => 'string']);

        $this->postJson("/api/sensors/{$site->slug}/solar", ['status_code' => 'OK'], [
            'X-API-Key' => $site->api_key,
        ])->assertStatus(201);

        $reading = SensorReading::where('site_parameter_id', $param->id)->first();
        $this->assertNull($reading->value);
        $this->assertSame('OK', $reading->value_text);
    }

    public function test_value_below_configured_minimum_is_rejected(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'tank_level', 'min_value' => 0, 'max_value' => 100]);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", ['tank_level' => -5], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString('below the configured minimum', $response->json('errors')[0]);
        $this->assertDatabaseMissing('sensor_readings', ['site_parameter_id' => $param->id]);
    }

    public function test_value_above_configured_maximum_is_rejected(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'tank_level', 'min_value' => 0, 'max_value' => 100]);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", ['tank_level' => 999], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString('above the configured maximum', $response->json('errors')[0]);
        $this->assertDatabaseMissing('sensor_readings', ['site_parameter_id' => $param->id]);
    }

    public function test_controllable_switch_reading_updates_reported_state_and_initializes_desired_state(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, [
            'slug' => 'valve_1', 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);

        $this->postJson("/api/sensors/{$site->slug}/solar", ['valve_1' => 1], [
            'X-API-Key' => $site->api_key,
        ])->assertStatus(201);

        $this->assertDatabaseHas('actuator_commands', [
            'site_parameter_id' => $param->id,
            'reported_state'    => 1,
            'desired_state'     => 1, // no prior command — initialized to match the report
        ]);
    }

    public function test_controllable_switch_reading_does_not_overwrite_an_existing_desired_state(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, [
            'slug' => 'valve_1', 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);

        // Dashboard already queued a command: turn it ON, device hasn't reported yet.
        ActuatorCommand::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'desired_state' => 1, 'reported_state' => 0,
        ]);

        // ESP32 reports it's currently OFF (about to catch up to the command).
        $this->postJson("/api/sensors/{$site->slug}/solar", ['valve_1' => 0], [
            'X-API-Key' => $site->api_key,
        ])->assertStatus(201);

        $this->assertDatabaseHas('actuator_commands', [
            'site_parameter_id' => $param->id,
            'reported_state'    => 0,
            'desired_state'     => 1, // unchanged — still pending, not clobbered by the report
        ]);
    }

    public function test_custom_read_at_is_honored_instead_of_the_server_clock(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output']);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [
            'solar_output' => 4.2,
            'read_at'      => '2026-03-15 09:30:00',
        ], ['X-API-Key' => $site->api_key]);

        $response->assertStatus(201);
        $reading = SensorReading::where('site_parameter_id', $param->id)->first();
        $this->assertSame('2026-03-15 09:30:00', $reading->read_at->format('Y-m-d H:i:s'));
    }

    public function test_read_at_is_never_stored_as_a_sensor_reading_itself(): void
    {
        // read_at is excluded from the params loop via except(['read_at']) —
        // confirm it never gets misread as a parameter slug named "read_at".
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $this->makeParam($category, ['slug' => 'solar_output']);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [
            'solar_output' => 4.2,
            'read_at'      => now()->toDateTimeString(),
        ], ['X-API-Key' => $site->api_key]);

        $this->assertArrayNotHasKey('read_at', $response->json('stored'));
    }

    public function test_empty_request_body_is_accepted_and_stores_nothing(): void
    {
        $site = $this->makeSite();
        $this->makeCategory($site);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true, 'stored' => [], 'errors' => []]);
        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_inactive_category_is_treated_as_not_found_for_ingestion(): void
    {
        $site = $this->makeSite();
        SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar', 'is_active' => false]);

        $this->postJson("/api/sensors/{$site->slug}/solar", ['x' => 1], ['X-API-Key' => $site->api_key])
            ->assertStatus(404);
    }

    public function test_inactive_parameter_is_reported_as_not_found_and_not_stored(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'solar_output', 'is_active' => false]);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", ['solar_output' => 4.2], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString("'solar_output'", $response->json('errors')[0]);
        $this->assertDatabaseMissing('sensor_readings', ['site_parameter_id' => $param->id]);
    }

    public function test_manual_input_parameter_cannot_be_written_through_the_sensor_endpoint(): void
    {
        // A manual (agent-entered) parameter must not be writable by a device
        // impersonating it — store() only matches input_type=sensor.
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $param = $this->makeParam($category, ['slug' => 'crop_yield', 'input_type' => 'manual']);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", ['crop_yield' => 99], [
            'X-API-Key' => $site->api_key,
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString("'crop_yield'", $response->json('errors')[0]);
        $this->assertDatabaseMissing('sensor_readings', ['site_parameter_id' => $param->id]);
    }

    public function test_multiple_parameters_in_a_single_request_are_all_stored_under_the_same_timestamp(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $power = $this->makeParam($category, ['slug' => 'solar_output']);
        $irradiance = $this->makeParam($category, ['slug' => 'solar_irradiance']);
        $temp = $this->makeParam($category, ['slug' => 'panel_temperature']);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [
            'solar_output'      => 4.2,
            'solar_irradiance'  => 890,
            'panel_temperature' => 54.1,
        ], ['X-API-Key' => $site->api_key]);

        $response->assertStatus(201);
        $this->assertCount(3, $response->json('stored'));
        $this->assertSame(0, count($response->json('errors')));

        $timestamps = SensorReading::whereIn('site_parameter_id', [$power->id, $irradiance->id, $temp->id])
            ->pluck('read_at')->unique();
        $this->assertCount(1, $timestamps); // all three share the single read_at from this request
    }

    public function test_a_mix_of_valid_and_invalid_parameters_stores_the_valid_ones_and_reports_the_rest(): void
    {
        $site = $this->makeSite();
        $category = $this->makeCategory($site);
        $good = $this->makeParam($category, ['slug' => 'solar_output', 'min_value' => 0, 'max_value' => 10]);
        $this->makeParam($category, ['slug' => 'irradiance', 'min_value' => 0, 'max_value' => 1500]);

        $response = $this->postJson("/api/sensors/{$site->slug}/solar", [
            'solar_output' => 4.2,     // valid
            'irradiance'   => 99999,   // over max, rejected
            'ghost_param'  => 1,       // unknown, rejected
        ], ['X-API-Key' => $site->api_key]);

        $response->assertStatus(201);
        $this->assertCount(1, $response->json('stored'));
        $this->assertCount(2, $response->json('errors'));
        $this->assertDatabaseHas('sensor_readings', ['site_parameter_id' => $good->id, 'value' => 4.2]);
        $this->assertDatabaseCount('sensor_readings', 1);
    }
}
