<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Mail\NewRegistrationMail;
use App\Models\ManualReading;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Defects found by the third audit, each confirmed by a probe before being
 * fixed: an account deleting itself took its sites and data with it, the
 * last administrator could remove its own rights, and several admin forms
 * accepted states that corrupt the model (two sites on one source, two
 * categories under one slug...).
 */
class AccountAndSiteSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow('2026-10-13 12:00:00');
    }

    private function admin(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => User::ROLE_ADMIN, 'status' => 'active', 'password' => bcrypt('secret-pass')]);
    }

    // ── accounts that must not disappear ─────────────────────────────

    public function test_deleting_your_own_account_cannot_take_sites_or_readings_with_it(): void
    {
        $this->admin();   // so the account under test isn't the last admin
        $owner = $this->admin();
        $site  = Site::factory()->create(['user_id' => $owner->id, 'status' => 'active', 'name' => 'AfriFarm']);
        $param = SiteParameter::factory()->create(['site_category_id' => SiteCategory::factory()->create(['site_id' => $site->id])->id]);
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => now()]);

        $this->actingAs($owner)->delete(route('profile.destroy'), ['password' => 'secret-pass'])
            ->assertSessionHasErrorsIn('userDeletion', ['password']);

        $this->assertNotNull($owner->fresh());
        $this->assertSame([1, 1], [Site::count(), SensorReading::count()]);
        $this->assertStringContainsString('AfriFarm', session('errors')->userDeletion->first('password'));

        // An agent who entered field measurements keeps them, too.
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'password' => bcrypt('secret-pass')]);
        ManualReading::create(['site_id' => $site->id, 'site_parameter_id' => $param->id, 'user_id' => $agent->id, 'value' => '3', 'reading_date' => now()]);
        $this->actingAs($agent)->delete(route('profile.destroy'), ['password' => 'secret-pass'])
            ->assertSessionHasErrorsIn('userDeletion', ['password']);
        $this->assertSame(1, ManualReading::count());

        // Someone with nothing attached can still leave.
        $free = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active', 'password' => bcrypt('secret-pass')]);
        $this->actingAs($free)->delete(route('profile.destroy'), ['password' => 'secret-pass'])->assertRedirect('/');
        $this->assertNull($free->fresh());
    }

    public function test_the_last_active_administrator_cannot_be_demoted_suspended_or_deleted(): void
    {
        $only = $this->admin(['name' => 'Only Admin']);
        // A suspended administrator doesn't count as one who can administer.
        $this->admin(['status' => 'suspended']);
        $form = ['name' => 'Only Admin', 'email' => $only->email, 'role' => User::ROLE_ADMIN, 'status' => 'active'];

        $this->actingAs($only)->put(route('admin.users.update', $only), ['role' => User::ROLE_OBSERVER] + $form)->assertSessionHasErrors('role');
        $this->put(route('admin.users.update', $only), ['status' => 'suspended'] + $form)->assertSessionHasErrors('role');
        $this->post(route('admin.users.suspend', $only))->assertSessionHas('error');
        $this->delete(route('admin.users.destroy', $only))->assertSessionHas('error');
        $this->delete(route('profile.destroy'), ['password' => 'secret-pass'])->assertSessionHasErrorsIn('userDeletion', ['password']);

        $this->assertSame([User::ROLE_ADMIN, 'active'], [$only->refresh()->role, $only->status]);

        // Unrelated edits to that account are still allowed...
        $this->put(route('admin.users.update', $only), ['name' => 'Renamed'] + $form)->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $only->refresh()->name);

        // ...and once a second administrator exists, the first can step down.
        $this->admin();
        $this->put(route('admin.users.update', $only), ['role' => User::ROLE_OBSERVER] + $form)->assertSessionHasNoErrors();
        $this->assertSame(User::ROLE_OBSERVER, $only->refresh()->role);
    }

    // ── admin forms that accepted corrupting states ──────────────────

    public function test_a_data_source_feeds_one_site_only(): void
    {
        $admin = $this->admin();
        Site::factory()->create(['status' => 'active', 'thingsboard_prefix' => 'UTG', 'licor_device_serial' => '22490036']);
        $other = Site::factory()->create(['status' => 'active']);
        $form  = ['name' => $other->name, 'country' => 'Gambia', 'user_id' => $admin->id, 'status' => 'active'];

        $this->actingAs($admin)->put(route('admin.sites.update', $other), $form + ['thingsboard_prefix' => 'UTG'])->assertSessionHasErrors('thingsboard_prefix');
        $this->put(route('admin.sites.update', $other), $form + ['licor_device_serial' => '22490036'])->assertSessionHasErrors('licor_device_serial');
        $this->assertNull($other->refresh()->thingsboard_prefix);

        // Its own values can of course be saved again.
        $this->put(route('admin.sites.update', $other), $form + ['thingsboard_prefix' => 'IPR'])->assertSessionHasNoErrors();
        $this->put(route('admin.sites.update', $other), $form + ['thingsboard_prefix' => 'IPR'])->assertSessionHasNoErrors();
    }

    public function test_linking_a_slow_source_raises_the_thresholds_of_categories_that_already_exist(): void
    {
        config(['thingsboard.url' => 'http://tb.test', 'thingsboard.username' => 'u', 'thingsboard.password' => 'p']);
        $admin = $this->admin();
        $category = fn (Site $site, string $slug, int $minutes) => SiteCategory::factory()->create(
            ['site_id' => $site->id, 'slug' => $slug, 'offline_threshold_minutes' => $minutes]);

        // Through the site form...
        $viaForm    = Site::factory()->create(['status' => 'active']);
        $irrigation = $category($viaForm, 'irrigation', 5);
        $long       = $category($viaForm, 'water', 180);      // an admin's longer choice is kept
        $solar      = $category($viaForm, 'solar', 5);        // not fed by ThingsBoard: untouched
        $this->actingAs($admin)->put(route('admin.sites.update', $viaForm),
            ['name' => $viaForm->name, 'country' => 'Mali', 'user_id' => $admin->id, 'status' => 'active', 'thingsboard_prefix' => 'IPR']);

        $this->assertSame([60, 180, 5], [$irrigation->refresh()->offline_threshold_minutes, $long->refresh()->offline_threshold_minutes, $solar->refresh()->offline_threshold_minutes]);

        // ...and through Admin → Data sources.
        $viaSources = Site::factory()->create(['status' => 'active']);
        $weather    = $category($viaSources, 'weather', 5);
        $this->post(route('admin.sources.link'), ['source' => 'licor', 'key' => '22225708', 'site_id' => $viaSources->id]);

        $this->assertSame(720, $weather->refresh()->offline_threshold_minutes);
    }

    public function test_a_site_built_from_the_default_template_is_visible_on_the_overview(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.sites.store'),
            ['name' => 'Template site', 'country' => 'Mali', 'user_id' => $admin->id, 'create_default_categories' => 1]);

        $site    = Site::where('name', 'Template site')->firstOrFail();
        $metrics = SiteParameter::whereHas('category', fn ($q) => $q->where('site_id', $site->id))->whereNotNull('metric')->pluck('metric', 'slug');

        $this->assertSame('solar_output', $metrics['solar_output']);
        $this->assertSame('air_temperature', $metrics['temperature']);
        $this->assertSame('soil_moisture', $metrics['zone_a_moisture']);
        $this->assertSame('tank_fill_level', $metrics['tank_fill_level']);

        // With a reading, it gets its tile like any synced site.
        $temp = SiteParameter::where('slug', 'temperature')->whereHas('category', fn ($q) => $q->where('site_id', $site->id))->first();
        SensorReading::factory()->create(['site_id' => $site->id, 'site_parameter_id' => $temp->id, 'value' => 31, 'read_at' => now()->subMinute()]);

        $tiles = $this->get(route('dashboard'))->viewData('cards')[$site->id]['tiles'];
        $this->assertSame(['air_temperature'], array_column($tiles, 'metric'));
    }

    public function test_two_categories_of_a_site_cannot_share_a_name(): void
    {
        $admin = $this->admin();
        $site  = Site::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->post(route('admin.categories.store', $site), ['name' => 'Solar'])->assertSessionHasNoErrors();
        $this->post(route('admin.categories.store', $site), ['name' => 'Solar'])->assertSessionHasErrors('name');
        $this->post(route('admin.categories.store', $site), ['name' => 'solar'])->assertSessionHasErrors('name');   // same slug

        $this->assertSame(1, $site->categories()->where('slug', 'solar')->count());

        // Another site may have its own "Solar".
        $this->post(route('admin.categories.store', Site::factory()->create()), ['name' => 'Solar'])->assertSessionHasNoErrors();
    }

    public function test_a_duplicated_site_starts_with_no_links_sync_results_or_alert_state(): void
    {
        $admin = $this->admin();
        $site  = Site::factory()->create(['status' => 'active', 'thingsboard_prefix' => 'UTG']);
        $site->forceFill(['thingsboard_synced_at' => now(), 'thingsboard_sync_error' => 'boom', 'licor_synced_at' => now()])->save();
        SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'irrigation'])->forceFill(['offline_since' => now()->subDay()])->save();

        $this->actingAs($admin)->post(route('admin.sites.duplicate', $site));

        $copy = Site::where('name', 'like', '%(Copy)')->firstOrFail();
        $this->assertNull($copy->thingsboard_prefix);
        $this->assertNull($copy->thingsboard_synced_at);
        $this->assertNull($copy->thingsboard_sync_error);
        $this->assertNull($copy->licor_synced_at);
        $this->assertNull($copy->categories()->first()->offline_since);
    }

    // ── manual readings ──────────────────────────────────────────────

    public function test_manual_readings_are_not_dated_in_the_future_nor_entered_on_parameters_out_of_use(): void
    {
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active']);
        $site  = Site::factory()->create(['user_id' => $agent->id, 'status' => 'active']);
        $cat   = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'agriculture', 'is_active' => true]);
        $inUse = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'input_type' => 'manual', 'is_active' => true]);
        $off   = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'input_type' => 'manual', 'is_active' => false]);
        $post  = fn (string $date, SiteParameter $p) => $this->actingAs($agent)->post(route('manual-readings.store', $site),
            ['reading_date' => $date, 'readings' => [['param_id' => $p->id, 'value' => 5]]]);

        $post('2031-01-01', $inUse)->assertSessionHasErrors('reading_date');
        $post('2026-10-13', $off)->assertSessionHasErrors();
        $this->assertSame(0, ManualReading::count());

        $post('2026-10-13', $inUse)->assertSessionHasNoErrors();
        $post('2026-09-01', $inUse)->assertSessionHasNoErrors();   // late entry of a past measurement
        $this->assertSame(2, ManualReading::count());
    }

    // ── registration and approval ────────────────────────────────────

    public function test_registration_keeps_what_the_form_asks_for_and_tells_the_administrators(): void
    {
        $this->admin(['email' => 'boss@example.test']);
        $this->admin(['email' => 'gone@example.test', 'status' => 'suspended']);

        $this->post('/register', [
            'name' => 'Awa Jallow', 'email' => 'awa@example.test',
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
            'organisation' => 'UTG', 'country' => 'Gambia', 'phone' => '+220 123',
        ]);

        $user = User::where('email', 'awa@example.test')->firstOrFail();
        $this->assertSame(['UTG', 'Gambia', '+220 123', 'pending'], [$user->organisation, $user->country, $user->phone, $user->status]);

        Mail::assertSent(NewRegistrationMail::class, fn ($mail) => $mail->hasTo('boss@example.test') && !$mail->hasTo('gone@example.test'));
        $this->assertStringContainsString('Awa Jallow', (new NewRegistrationMail($user))->render());
    }

    public function test_approving_through_the_edit_form_sends_the_approval_mail_once(): void
    {
        $admin   = $this->admin();
        $pending = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'pending']);
        $form    = ['name' => $pending->name, 'email' => $pending->email, 'role' => User::ROLE_AGENT];

        $this->actingAs($admin)->put(route('admin.users.update', $pending), $form + ['status' => 'active']);
        Mail::assertSent(AccountApprovedMail::class, 1);

        // Saving an already-active account again says nothing.
        $this->put(route('admin.users.update', $pending), $form + ['status' => 'active']);
        Mail::assertSent(AccountApprovedMail::class, 1);
    }

    // ── names are text ───────────────────────────────────────────────

    public function test_names_with_quotes_or_ampersands_reach_the_charts_intact(): void
    {
        $admin = $this->admin();
        $site  = Site::factory()->create(['status' => 'active']);
        $cat   = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'weather', 'is_active' => true]);
        $param = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'name' => "Farmer's plot & tank", 'unit' => '%', 'is_active' => true]);
        $chart = $cat->charts()->create(['title' => 'Chart', 'chart_type' => 'line', 'is_active' => true]);
        $chart->chartParameters()->create(['site_parameter_id' => $param->id]);

        $html = $this->actingAs($admin)->get(route('dashboard.site', $site))->assertOk()->getContent();

        // As a JSON string in the page script, not HTML-escaped text inside quotes.
        $asJson = json_encode("Farmer's plot & tank (%)", JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $this->assertStringContainsString('label: ' . $asJson, $html);
        $this->assertStringNotContainsString("label: 'Farmer", $html);
    }

    public function test_a_sensor_switched_off_leaves_the_charts_that_list_it(): void
    {
        $site = Site::factory()->create(['status' => 'active']);
        $cat  = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'weather', 'is_active' => true]);
        $on   = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'name' => 'Wind speed – live', 'is_active' => true]);
        $off  = SiteParameter::factory()->create(['site_category_id' => $cat->id, 'name' => 'Wind speed – phantom', 'is_active' => false]);
        $chart = $cat->charts()->create(['title' => 'Wind', 'chart_type' => 'line', 'is_active' => true]);
        $chart->chartParameters()->create(['site_parameter_id' => $on->id]);
        $chart->chartParameters()->create(['site_parameter_id' => $off->id]);

        $this->actingAs($this->admin())->get(route('dashboard.site', $site))->assertOk()
            ->assertSee('Wind speed – live')->assertDontSee('Wind speed – phantom');
    }

    public function test_a_field_group_keeps_its_label_with_a_single_sensor(): void
    {
        $admin = $this->admin();
        $site  = Site::factory()->create(['status' => 'active']);
        $cat   = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'irrigation', 'is_active' => true]);
        $group = \App\Models\SiteParameterGroup::create(['site_category_id' => $cat->id, 'name' => 'Business field', 'color' => '#8b5cf6', 'sort_order' => 2]);
        SiteParameter::factory()->create([
            'site_category_id' => $cat->id, 'name' => 'Business 0 – Valve state', 'data_type' => 'switch',
            'group_name' => 'Business field', 'site_parameter_group_id' => $group->id, 'is_active' => true,
        ]);

        $html = $this->actingAs($admin)->get(route('dashboard.site', $site))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="pg-head"[^>]*>\s*<span class="pg-dot"[^>]*><\/span>\s*Business field/', $html);
    }
}
