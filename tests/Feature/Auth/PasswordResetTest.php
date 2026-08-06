<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * User::sendPasswordResetNotification() bypasses Laravel's Notification
 * system and sends a custom Mailable directly (see app/Models/User.php),
 * so these tests assert against Mail::fake(), not Notification::fake().
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(ResetPasswordMail::class, fn ($mail) => $mail->user->is($user));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(ResetPasswordMail::class, function ($mail) {
            $token = $this->tokenFromResetUrl($mail->resetUrl);

            $this->get('/reset-password/'.$token)->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
            $token = $this->tokenFromResetUrl($mail->resetUrl);

            $response = $this->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    private function tokenFromResetUrl(string $resetUrl): string
    {
        // route('password.reset', ['token' => ..., 'email' => ...]) puts the
        // token in the path (reset-password/{token}) and email in the query.
        $path = parse_url($resetUrl, PHP_URL_PATH);

        return basename($path);
    }
}
