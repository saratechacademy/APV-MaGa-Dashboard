<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Mail\SiteAccessMail;
use App\Mail\WelcomeUserMail;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sending is wrapped in try/catch throughout the app, so a template that no
 * longer renders fails silently: the account is created and nobody is told.
 * Every transactional e-mail is rendered here for real.
 */
class MailRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_and_approval_mails_render_with_a_readable_role(): void
    {
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER, 'name' => 'Awa']);

        $welcome = (new WelcomeUserMail($observer))->render();
        $this->assertStringContainsString('Welcome, Awa!', $welcome);
        $this->assertStringContainsString('>Observer<', $welcome);
        $this->assertStringNotContainsString('bservateur', $welcome);

        $this->assertStringContainsString('Admin', (new WelcomeUserMail(User::factory()->create(['role' => User::ROLE_ADMIN])))->render());
        $this->assertNotEmpty((new AccountApprovedMail($observer))->render());
    }

    public function test_site_access_mail_renders_the_role_held_on_that_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['name' => 'AfriFarm']);

        $asObserver = (new SiteAccessMail($user, $site, User::ROLE_OBSERVER))->render();
        $this->assertStringContainsString('AfriFarm', $asObserver);
        $this->assertStringContainsString('>Observer<', $asObserver);
        $this->assertStringContainsString('As an <strong>observer</strong>', $asObserver);

        $asAgent = (new SiteAccessMail($user, $site, User::ROLE_AGENT))->render();
        $this->assertStringContainsString('>Agent<', $asAgent);
        $this->assertStringContainsString('As an <strong>agent</strong>', $asAgent);
    }
}
