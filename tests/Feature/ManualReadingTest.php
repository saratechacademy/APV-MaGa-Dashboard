<?php

namespace Tests\Feature;

use App\Models\ManualReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ManualReadingController::store() is the only way field data (crop yield,
 * plant health...) ever enters the system — SiteAuthorizationTest already
 * covers that observers are blocked, this covers that agents/admins actually
 * succeed and the data lands correctly.
 */
class ManualReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_submit_manual_readings_and_they_are_stored_correctly(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'agriculture']);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'input_type' => 'manual', 'data_type' => 'float', 'slug' => 'crop_yield',
        ]);

        $response = $this->actingAs($agent)->post(route('manual-readings.store', $site), [
            'reading_date' => '2026-03-01',
            'readings'     => [['param_id' => $param->id, 'value' => '42.5']],
            'notes'        => 'Good harvest',
        ]);

        $response->assertRedirect(route('dashboard.site', $site));

        $reading = ManualReading::where('site_parameter_id', $param->id)->first();
        $this->assertNotNull($reading);
        $this->assertSame($site->id, $reading->site_id);
        $this->assertSame($agent->id, $reading->user_id);
        $this->assertEquals(42.5, $reading->value);
        $this->assertSame('2026-03-01', $reading->reading_date->format('Y-m-d'));
        $this->assertSame('Good harvest', $reading->notes);
    }

    public function test_empty_values_are_not_saved_as_readings(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param1   = SiteParameter::factory()->create(['site_category_id' => $category->id, 'input_type' => 'manual']);
        $param2   = SiteParameter::factory()->create(['site_category_id' => $category->id, 'input_type' => 'manual']);

        $this->actingAs($agent)->post(route('manual-readings.store', $site), [
            'reading_date' => now()->toDateString(),
            'readings'     => [
                ['param_id' => $param1->id, 'value' => '10'],
                ['param_id' => $param2->id, 'value' => ''], // left blank in the form
            ],
        ]);

        $this->assertDatabaseHas('manual_readings', ['site_parameter_id' => $param1->id]);
        $this->assertDatabaseMissing('manual_readings', ['site_parameter_id' => $param2->id]);
    }

    public function test_admin_can_submit_manual_readings_on_any_site(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $owner    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $owner->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'input_type' => 'manual']);

        $this->actingAs($admin)->post(route('manual-readings.store', $site), [
            'reading_date' => now()->toDateString(),
            'readings'     => [['param_id' => $param->id, 'value' => '7']],
        ])->assertRedirect();

        $this->assertDatabaseHas('manual_readings', ['site_parameter_id' => $param->id, 'user_id' => $admin->id]);
    }

    public function test_param_id_belonging_to_a_different_site_is_rejected(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $site        = Site::factory()->create(['user_id' => $agent->id]);
        $siteCat     = SiteCategory::factory()->create(['site_id' => $site->id]);

        $otherSite   = Site::factory()->create();
        $otherCat    = SiteCategory::factory()->create(['site_id' => $otherSite->id]);
        $foreignParam = SiteParameter::factory()->create(['site_category_id' => $otherCat->id, 'input_type' => 'manual']);

        $response = $this->actingAs($agent)->post(route('manual-readings.store', $site), [
            'reading_date' => now()->toDateString(),
            'readings'     => [['param_id' => $foreignParam->id, 'value' => '10']],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('manual_readings', ['site_parameter_id' => $foreignParam->id]);
    }

    public function test_value_outside_configured_min_max_is_rejected(): void
    {
        $agent    = User::factory()->create(['role' => 'agent']);
        $site     = Site::factory()->create(['user_id' => $agent->id]);
        $category = SiteCategory::factory()->create(['site_id' => $site->id]);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'input_type' => 'manual',
            'data_type' => 'float', 'min_value' => 0, 'max_value' => 100,
        ]);

        $response = $this->actingAs($agent)->post(route('manual-readings.store', $site), [
            'reading_date' => now()->toDateString(),
            'readings'     => [['param_id' => $param->id, 'value' => '150']],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('manual_readings', ['site_parameter_id' => $param->id]);
    }
}
