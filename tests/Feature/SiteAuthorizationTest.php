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
 * Regression tests for the authorization audit: every site-scoped route must
 * check access via authorizeSiteAccess() (site_user pivot OR legacy user_id
 * OR admin), and observers must never be able to write data even on sites
 * they can view.
 */
class SiteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status = 'active'): User
    {
        return User::factory()->create(['role' => $role, 'status' => $status]);
    }

    private function makeSite(User $owner, string $name = 'Test Site'): Site
    {
        $site = Site::create([
            'user_id' => $owner->id,
            'name'    => $name,
            'country' => 'Niger',
            'status'  => 'active',
        ]);

        $category = $site->categories()->create([
            'name' => 'Solar', 'slug' => 'solar', 'is_active' => true, 'sort_order' => 0,
        ]);

        $category->parameters()->create([
            'name' => 'Pump', 'slug' => 'pump', 'data_type' => 'switch',
            'input_type' => 'sensor', 'control_type' => 'controllable',
            'is_active' => true, 'show_on_dashboard' => true, 'sort_order' => 0,
        ]);

        return $site;
    }

    public function test_admin_can_access_any_site(): void
    {
        $admin = $this->makeUser('admin');
        $site  = $this->makeSite($this->makeUser('agent'));

        $this->actingAs($admin)->get(route('dashboard.site', $site))->assertOk();
    }

    public function test_user_with_no_assignment_is_denied(): void
    {
        $stranger = $this->makeUser('agent');
        $site     = $this->makeSite($this->makeUser('agent'));

        $this->actingAs($stranger)->get(route('dashboard.site', $site))->assertForbidden();
        $this->actingAs($stranger)->get(route('export.all.csv', $site))->assertForbidden();
    }

    public function test_pivot_only_assignment_grants_access_everywhere(): void
    {
        // Regression: agent assigned via site_user pivot (not site.user_id)
        // used to be denied by ExportController/ManualReadingController,
        // which only checked the legacy direct-owner field.
        $owner = $this->makeUser('agent');
        $agent = $this->makeUser('agent');
        $site  = $this->makeSite($owner);
        $site->users()->attach($agent->id, ['role' => 'agent']);

        $this->actingAs($agent)->get(route('dashboard.site', $site))->assertOk();
        $this->actingAs($agent)->get(route('export.all.csv', $site))->assertOk();
    }

    public function test_observateur_cannot_toggle_actuator_even_on_own_site(): void
    {
        $observer = $this->makeUser('observateur');
        $site     = $this->makeSite($observer);
        $param    = $site->categories()->first()->parameters()->first();

        $this->actingAs($observer)
            ->post(route('actuators.toggle', [$site, $param]), ['state' => 1])
            ->assertForbidden();

        $this->assertDatabaseMissing('actuator_commands', ['site_parameter_id' => $param->id]);
    }

    public function test_observateur_cannot_submit_manual_readings(): void
    {
        $observer = $this->makeUser('observateur');
        $site     = $this->makeSite($observer);

        $this->actingAs($observer)
            ->post(route('manual-readings.store', $site), [
                'reading_date' => now()->toDateString(),
                'readings'     => [['param_id' => 1, 'value' => '5']],
            ])
            ->assertForbidden();
    }

    public function test_agent_can_toggle_actuator_on_own_site(): void
    {
        $agent = $this->makeUser('agent');
        $site  = $this->makeSite($agent);
        $param = $site->categories()->first()->parameters()->first();

        $this->actingAs($agent)
            ->post(route('actuators.toggle', [$site, $param]), ['state' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('actuator_commands', [
            'site_parameter_id' => $param->id,
            'desired_state'     => 1,
        ]);
    }

    public function test_observateur_can_use_all_sites_export_but_only_sees_own_sites(): void
    {
        // Observers are typically researchers: they should be able to use
        // every export button, but "all sites" must stay scoped to sites
        // they're actually assigned to — not the whole platform's data.
        $observer  = $this->makeUser('observateur');
        $ownSite   = $this->makeSite($observer, 'Observer Own Site');
        $otherSite = $this->makeSite($this->makeUser('agent'), 'Unrelated Site');

        $response = $this->actingAs($observer)->get(route('export.all-sites.csv'));

        $response->assertOk();
        $content = $this->streamedCsvContent($response);
        $this->assertStringContainsString($ownSite->name, $content);
        $this->assertStringNotContainsString($otherSite->name, $content);
    }

    public function test_stranger_with_no_sites_gets_empty_all_sites_export(): void
    {
        $stranger = $this->makeUser('agent');
        $this->makeSite($this->makeUser('agent'));

        $this->actingAs($stranger)->get(route('export.all-sites.csv'))->assertOk();
    }

    public function test_admin_sees_every_site_in_all_sites_export(): void
    {
        $admin = $this->makeUser('admin');
        $site  = $this->makeSite($this->makeUser('agent'));

        $response = $this->actingAs($admin)->get(route('export.all-sites.csv'));

        $response->assertOk();
        $content = $this->streamedCsvContent($response);
        $this->assertStringContainsString($site->name, $content);
    }

    /**
     * response()->stream() callbacks only run on send(); TestResponse's
     * getContent()/assertSee() see nothing for them, so capture manually.
     */
    private function streamedCsvContent($response): string
    {
        ob_start();
        $response->baseResponse->sendContent();
        return ob_get_clean();
    }

    public function test_deleting_user_who_owns_a_site_is_blocked(): void
    {
        $admin = $this->makeUser('admin');
        $owner = $this->makeUser('agent');
        $this->makeSite($owner);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $owner))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_deleting_user_without_sites_succeeds(): void
    {
        $admin  = $this->makeUser('admin');
        $target = $this->makeUser('agent');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
