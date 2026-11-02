<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_valid_credentials_start_the_two_factor_challenge_without_logging_in(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));
        Notification::assertSentTo($user, TwoFactorCode::class);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_pending_users_can_not_log_in(): void
    {
        Notification::fake();
        $user = User::factory()->pending()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('two_factor.user_id');
        Notification::assertNothingSent();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_whose_access_was_revoked_are_logged_out(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
