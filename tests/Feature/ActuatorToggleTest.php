<?php

namespace Tests\Feature;

use App\Models\ActuatorCommand;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SiteAuthorizationTest already covers the observer-blocked / agent-allowed
 * split. This covers the remaining robustness checks in
 * ActuatorController::toggle: cross-site parameter spoofing, non-controllable
 * parameters, and invalid state values — a physical relay/valve/pump sits
 * behind this endpoint, so silently accepting a bad command is a real-world
 * risk, not just a UI glitch.
 */
class ActuatorToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameter_belonging_to_a_different_site_returns_404(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $site  = Site::factory()->create(['user_id' => $agent->id]);

        $otherSite = Site::factory()->create();
        $otherCat  = SiteCategory::factory()->create(['site_id' => $otherSite->id]);
        $foreignParam = SiteParameter::factory()->create([
            'site_category_id' => $otherCat->id, 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);

        $this->actingAs($agent)
            ->post(route('actuators.toggle', [$site, $foreignParam]), ['state' => 1])
            ->assertNotFound();

        $this->assertDatabaseMissing('actuator_commands', ['site_parameter_id' => $foreignParam->id]);
    }

    public function test_non_controllable_parameter_is_rejected_without_creating_a_command(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'data_type' => 'float', 'control_type' => 'readonly',
        ]);

        $response = $this->actingAs($agent)
            ->post(route('actuators.toggle', [$site, $param]), ['state' => 1]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('actuator_commands', ['site_parameter_id' => $param->id]);
    }

    public function test_invalid_state_value_is_rejected(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);

        $response = $this->actingAs($agent)
            ->post(route('actuators.toggle', [$site, $param]), ['state' => 'on']);

        $response->assertSessionHasErrors('state');
        $this->assertDatabaseMissing('actuator_commands', ['site_parameter_id' => $param->id]);
    }

    public function test_toggling_an_existing_command_updates_it_instead_of_duplicating(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);
        ActuatorCommand::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id, 'desired_state' => 0,
        ]);

        $this->actingAs($agent)
            ->post(route('actuators.toggle', [$site, $param]), ['state' => 1])
            ->assertRedirect();

        $this->assertSame(1, ActuatorCommand::where('site_parameter_id', $param->id)->count());
        $this->assertDatabaseHas('actuator_commands', [
            'site_parameter_id' => $param->id, 'desired_state' => 1, 'updated_by' => $agent->id,
        ]);
    }
}
