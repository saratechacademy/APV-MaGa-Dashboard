<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwaitingSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_solar_tab_announces_pv_data_until_the_site_has_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $site  = Site::factory()->create(['status' => 'active', 'user_id' => $admin->id]);
        $solar = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar', 'name' => 'Solar', 'is_active' => true]);
        SiteParameter::factory()->create(['site_category_id' => $solar->id, 'is_active' => true, 'metric' => 'par_reference']);

        $this->actingAs($admin)->get(route('dashboard.site', $site))
            ->assertOk()
            ->assertSee('Photovoltaic production — coming soon');

        SiteParameter::factory()->create(['site_category_id' => $solar->id, 'is_active' => true, 'metric' => 'solar_output']);

        $this->actingAs($admin)->get(route('dashboard.site', $site))
            ->assertOk()
            ->assertDontSee('Photovoltaic production — coming soon');
    }
}
