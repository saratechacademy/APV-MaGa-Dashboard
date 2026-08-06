<?php

namespace Tests\Feature\Admin;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminController::storeParameter/updateParameter silently coerce several
 * fields (control_type, input_type, threshold_direction) rather than
 * rejecting inconsistent combinations — this locks that coercion in place so
 * a future refactor can't let a non-switch parameter become "controllable"
 * or a switch stay in "manual" input mode.
 */
class ParameterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(): SiteCategory
    {
        $site = Site::factory()->create();
        return SiteCategory::factory()->create(['site_id' => $site->id]);
    }

    public function test_max_value_below_min_value_is_rejected(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        $response = $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            [
                'name' => 'Bad Range', 'data_type' => 'float',
                'min_value' => 100, 'max_value' => 10,
            ]
        );

        $response->assertSessionHasErrors('max_value');
        $this->assertDatabaseMissing('site_parameters', ['name' => 'Bad Range']);
    }

    public function test_control_type_is_forced_to_readonly_for_non_switch_parameters(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        // Someone tampering with the form (or a stale client) sends
        // control_type=controllable on a plain float sensor — must not stick.
        $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Solar Output', 'data_type' => 'float', 'control_type' => 'controllable']
        )->assertRedirect();

        $param = SiteParameter::where('name', 'Solar Output')->first();
        $this->assertSame('readonly', $param->control_type);
    }

    public function test_switch_parameter_can_be_made_controllable(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Valve 1', 'data_type' => 'switch', 'control_type' => 'controllable']
        )->assertRedirect();

        $param = SiteParameter::where('name', 'Valve 1')->first();
        $this->assertSame('controllable', $param->control_type);
    }

    public function test_switch_parameter_is_always_forced_to_sensor_input_type(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        // Trying to make a switch "manual" (agent-entered) doesn't make sense —
        // switches are always API/ESP32-driven.
        $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Pump', 'data_type' => 'switch', 'input_type' => 'manual']
        )->assertRedirect();

        $param = SiteParameter::where('name', 'Pump')->first();
        $this->assertSame('sensor', $param->input_type);
    }

    public function test_threshold_direction_defaults_to_below_when_not_provided(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Tank Level', 'data_type' => 'float']
        )->assertRedirect();

        $param = SiteParameter::where('name', 'Tank Level')->first();
        $this->assertSame('below', $param->threshold_direction);
    }

    public function test_threshold_direction_above_is_preserved_when_explicitly_set(): void
    {
        $admin    = $this->admin();
        $category = $this->category();

        $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Panel Temp', 'data_type' => 'float', 'threshold_direction' => 'above']
        )->assertRedirect();

        $param = SiteParameter::where('name', 'Panel Temp')->first();
        $this->assertSame('above', $param->threshold_direction);
    }

    public function test_duplicate_slug_within_the_same_category_is_rejected(): void
    {
        $admin    = $this->admin();
        $category = $this->category();
        SiteParameter::factory()->create(['site_category_id' => $category->id, 'name' => 'Solar Output', 'slug' => 'solar_output']);

        $response = $this->actingAs($admin)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Solar Output', 'data_type' => 'float']
        );

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, SiteParameter::where('slug', 'solar_output')->count());
    }

    public function test_non_admin_cannot_create_parameters(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $category = $this->category();

        $this->actingAs($agent)->post(
            route('admin.parameters.store', [$category->site, $category]),
            ['name' => 'Sneaky Param', 'data_type' => 'float']
        )->assertForbidden();

        $this->assertDatabaseMissing('site_parameters', ['name' => 'Sneaky Param']);
    }

    public function test_update_also_rejects_max_value_below_min_value(): void
    {
        $admin    = $this->admin();
        $category = $this->category();
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id]);

        $response = $this->actingAs($admin)->put(
            route('admin.parameters.update', [$category->site, $category, $param]),
            ['name' => $param->name, 'data_type' => 'float', 'min_value' => 50, 'max_value' => 5]
        );

        $response->assertSessionHasErrors('max_value');
    }

    public function test_update_forces_control_type_readonly_when_data_type_changes_away_from_switch(): void
    {
        $admin    = $this->admin();
        $category = $this->category();
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'data_type' => 'switch', 'control_type' => 'controllable',
        ]);

        $this->actingAs($admin)->put(
            route('admin.parameters.update', [$category->site, $category, $param]),
            ['name' => $param->name, 'data_type' => 'float', 'control_type' => 'controllable']
        )->assertRedirect();

        $this->assertSame('readonly', $param->fresh()->control_type);
    }
}
