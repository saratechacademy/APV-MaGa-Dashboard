<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CheckUserStatus is the first line of defense against pending/suspended
 * accounts still using an active session — nothing else in the auth stack
 * re-checks status per-request, so a regression here would let a suspended
 * user keep working until their session naturally expires.
 */
class UserStatusMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_user_is_logged_out_and_redirected_with_an_error(): void
    {
        $user = User::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_is_logged_out_and_redirected_with_an_error(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_active_user_passes_through_normally(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_suspended_user_cannot_use_a_still_valid_session_to_reach_any_protected_route(): void
    {
        // Regression scenario: user was active when they logged in, an admin
        // suspends them mid-session — the very next request must still catch it.
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->status = 'suspended';
        $user->save();

        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));
    }
}
