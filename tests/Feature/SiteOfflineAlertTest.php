<?php

namespace Tests\Feature;

use App\Mail\SiteStatusMail;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Offline alerts are e-mails to partners: one too many is spam, one missing
 * is a field team that doesn't know its probes are dead. These tests pin down
 * when exactly a message goes out, to whom, and that it goes out once.
 */
class SiteOfflineAlertTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow('2026-10-13 12:00:00');

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'email' => 'admin@example.test']);
        $this->site  = Site::factory()->create(['name' => 'UTG', 'status' => 'active', 'user_id' => $this->admin->id]);
    }

    private function category(string $slug, int $thresholdMinutes = 60, ?Site $site = null): SiteCategory
    {
        return SiteCategory::factory()->create([
            'site_id' => ($site ?? $this->site)->id, 'slug' => $slug, 'name' => ucfirst($slug),
            'is_active' => true, 'offline_threshold_minutes' => $thresholdMinutes,
        ]);
    }

    private function reading(SiteCategory $category, string $at, array $paramAttrs = []): SiteParameter
    {
        $param = SiteParameter::factory()->create(['site_category_id' => $category->id, 'is_active' => true] + $paramAttrs);
        SensorReading::factory()->create(['site_id' => $category->site_id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => $at]);

        return $param;
    }

    private function newReading(SiteParameter $param, string $at): void
    {
        SensorReading::factory()->create(['site_id' => $this->site->id, 'site_parameter_id' => $param->id, 'value' => 1, 'read_at' => $at]);
    }

    public function test_one_message_when_part_of_a_site_stops_and_one_when_it_comes_back(): void
    {
        $irrigation = $this->category('irrigation');
        $weather    = $this->category('weather', 720);
        $probe      = $this->reading($irrigation, '2026-10-13 11:50:00');
        $this->reading($weather, '2026-10-13 06:00:00');   // 6 h old, but weather uploads in batches: fine

        $this->artisan('sites:check-offline')->expectsOutputToContain('No change')->assertSuccessful();
        Mail::assertNothingSent();

        // The probes go quiet. Past the category's threshold, one alert goes out...
        Carbon::setTestNow('2026-10-13 13:00:00');
        $this->artisan('sites:check-offline')
            ->expectsOutputToContain('UTG: stopped — Irrigation. 1 recipient(s).')
            ->assertSuccessful();

        Mail::assertSent(SiteStatusMail::class, 1);
        Mail::assertSent(SiteStatusMail::class, function (SiteStatusMail $mail) {
            $mail->build();

            return $mail->hasTo('admin@example.test')
                && $mail->subject === 'UTG: Irrigation stopped reporting'
                && $mail->down[0]['since']->toDateTimeString() === '2026-10-13 11:50:00'
                && $mail->up === [];
        });
        $this->assertSame('2026-10-13 11:50:00', $irrigation->refresh()->offline_since->toDateTimeString());

        // ...and only one, however long the outage lasts.
        Carbon::setTestNow('2026-10-14 13:00:00');
        $this->newReading(SiteParameter::where('site_category_id', $weather->id)->first(), '2026-10-14 12:00:00');
        $this->artisan('sites:check-offline')->assertSuccessful();
        Mail::assertSent(SiteStatusMail::class, 1);

        // Data comes back: one message saying so, with how long it was silent.
        $this->newReading($probe, '2026-10-14 12:55:00');
        $this->artisan('sites:check-offline')
            ->expectsOutputToContain('UTG: back — Irrigation.')
            ->assertSuccessful();

        Mail::assertSent(SiteStatusMail::class, 2);
        Mail::assertSent(SiteStatusMail::class, function (SiteStatusMail $mail) {
            $mail->build();

            return $mail->subject === 'UTG: Irrigation reporting again'
                && str_contains($mail->render(), 'Silent for 1 day');
        });
        $this->assertNull($irrigation->refresh()->offline_since);
    }

    public function test_alerts_go_to_active_admins_and_to_the_active_users_of_that_site_only(): void
    {
        $owner    = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'email' => 'owner@example.test']);
        $this->site->update(['user_id' => $owner->id]);

        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active', 'email' => 'observer@example.test']);
        $this->site->users()->attach($observer->id, ['role' => User::ROLE_OBSERVER]);
        // Assigned twice over (owner and pivot) still means one e-mail.
        $this->site->users()->attach($owner->id, ['role' => User::ROLE_AGENT]);

        $suspended = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'suspended', 'email' => 'suspended@example.test']);
        $this->site->users()->attach($suspended->id, ['role' => User::ROLE_AGENT]);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'pending', 'email' => 'pending-admin@example.test']);

        $elsewhere = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'email' => 'elsewhere@example.test']);
        Site::factory()->create(['status' => 'active'])->users()->attach($elsewhere->id, ['role' => User::ROLE_AGENT]);

        $this->reading($this->category('irrigation'), '2026-10-13 09:00:00');

        $this->artisan('sites:check-offline')->expectsOutputToContain('3 recipient(s)')->assertSuccessful();

        Mail::assertSent(SiteStatusMail::class, fn (SiteStatusMail $mail) =>
            $mail->hasTo('admin@example.test') && $mail->hasTo('owner@example.test') && $mail->hasTo('observer@example.test')
            && !$mail->hasTo('suspended@example.test') && !$mail->hasTo('pending-admin@example.test') && !$mail->hasTo('elsewhere@example.test'));
    }

    public function test_users_who_do_not_take_alerts_are_left_out_whatever_their_role(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active', 'email' => 'student@example.test', 'receives_alerts' => false]);
        $this->site->users()->attach($student->id, ['role' => User::ROLE_OBSERVER]);
        $assistant = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'email' => 'assistant@example.test', 'receives_alerts' => true]);
        $this->site->users()->attach($assistant->id, ['role' => User::ROLE_AGENT]);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'email' => 'quiet-admin@example.test', 'receives_alerts' => false]);

        $this->reading($this->category('irrigation'), '2026-10-13 09:00:00');

        $this->artisan('sites:check-offline')->expectsOutputToContain('2 recipient(s)')->assertSuccessful();

        Mail::assertSent(SiteStatusMail::class, fn (SiteStatusMail $mail) =>
            $mail->hasTo('admin@example.test') && $mail->hasTo('assistant@example.test')
            && !$mail->hasTo('student@example.test') && !$mail->hasTo('quiet-admin@example.test'));
    }

    public function test_the_alert_choice_is_set_in_the_user_forms_and_defaults_off_for_self_registration(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Field assistant', 'email' => 'field@example.test', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
            'role' => User::ROLE_AGENT, 'status' => 'active', 'receives_alerts' => '1',
        ])->assertRedirect(route('admin.users'));
        $this->assertTrue(User::where('email', 'field@example.test')->first()->receives_alerts);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Student', 'email' => 'student@example.test', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
            'role' => User::ROLE_OBSERVER, 'status' => 'active', 'receives_alerts' => '0',
        ])->assertRedirect(route('admin.users'));
        $student = User::where('email', 'student@example.test')->first();
        $this->assertFalse($student->receives_alerts);

        $this->actingAs($this->admin)->put(route('admin.users.update', $student), [
            'name' => 'Student', 'email' => 'student@example.test', 'role' => User::ROLE_OBSERVER, 'status' => 'active', 'receives_alerts' => '1',
        ])->assertRedirect(route('admin.users'));
        $this->assertTrue($student->refresh()->receives_alerts);

        auth()->logout();
        $this->post('/register', [
            'name' => 'Visitor', 'email' => 'visitor@example.test', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
        ]);
        $this->assertFalse(User::where('email', 'visitor@example.test')->firstOrFail()->refresh()->receives_alerts);
    }

    public function test_several_categories_of_a_site_share_one_message(): void
    {
        $this->reading($this->category('irrigation'), '2026-10-13 09:00:00');
        $this->reading($this->category('water'), '2026-10-13 09:05:00');

        $this->artisan('sites:check-offline')->assertSuccessful();

        Mail::assertSent(SiteStatusMail::class, 1);
        Mail::assertSent(SiteStatusMail::class, function (SiteStatusMail $mail) {
            $mail->build();

            return $mail->subject === 'UTG: Irrigation, Water stopped reporting';
        });
    }

    public function test_nothing_is_announced_for_what_never_reported_is_switched_off_or_is_our_own_link_failing(): void
    {
        // Never reported: nothing stopped.
        SiteParameter::factory()->create(['site_category_id' => $this->category('solar')->id, 'is_active' => true]);

        // A category the admin deactivated, and a manual-entry one.
        $off = $this->category('water');
        $this->reading($off, '2026-10-01 09:00:00');
        $off->update(['is_active' => false]);
        $this->reading($this->category('agriculture'), '2026-10-01 09:00:00', ['input_type' => 'manual']);

        // A site set to inactive.
        $inactive = Site::factory()->create(['status' => 'active']);
        $this->reading($this->category('irrigation', 60, $inactive), '2026-10-01 09:00:00');
        $inactive->status = 'inactive';
        $inactive->save();

        // Our own sync to the data source is failing: silence proves nothing about the site.
        $unreachable = Site::factory()->create(['status' => 'active']);
        $unreachable->forceFill(['thingsboard_prefix' => 'X', 'thingsboard_sync_error' => 'Connection refused'])->save();
        $this->reading($this->category('irrigation', 60, $unreachable), '2026-10-01 09:00:00');

        $this->artisan('sites:check-offline')->expectsOutputToContain('No change')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_a_message_that_could_not_be_sent_is_tried_again_at_the_next_check(): void
    {
        $category = $this->category('irrigation');
        $this->reading($category, '2026-10-13 09:00:00');

        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));
        $this->artisan('sites:check-offline')->assertSuccessful();
        $this->assertNull($category->refresh()->offline_since);

        // Mail works again.
        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();
        $this->artisan('sites:check-offline')->assertSuccessful();
        Mail::assertSent(SiteStatusMail::class, 1);
        $this->assertNotNull($category->refresh()->offline_since);
    }
}
