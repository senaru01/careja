<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the first login step and return the code that was emailed.
     */
    private function startChallenge(User $user): string
    {
        Notification::fake();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $code = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function (TwoFactorCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    public function test_challenge_screen_redirects_to_login_without_a_pending_challenge(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_challenge_screen_can_be_rendered_after_valid_credentials(): void
    {
        $this->startChallenge(User::factory()->create());

        $this->get(route('two-factor.challenge'))->assertOk();
    }

    public function test_users_can_log_in_with_the_emailed_code(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);

        $response = $this->post(route('two-factor.challenge'), ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNull($user->fresh()->two_factor_code);
    }

    public function test_users_can_not_log_in_with_a_wrong_code(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);
        $wrongCode = $code === '111111' ? '222222' : '111111';

        $response = $this->post(route('two-factor.challenge'), ['code' => $wrongCode]);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_users_can_not_log_in_with_an_expired_code(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);

        $this->travel(User::TWO_FACTOR_CODE_LIFETIME + 1)->minutes();

        $response = $this->post(route('two-factor.challenge'), ['code' => $code]);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_code_can_not_be_reused_after_logging_in(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);
        $this->post(route('two-factor.challenge'), ['code' => $code]);
        $this->post('/logout');

        $this->post(route('two-factor.challenge'), ['code' => $code])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_challenge_is_locked_after_too_many_wrong_codes(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);
        $wrongCode = $code === '111111' ? '222222' : '111111';

        foreach (range(1, 5) as $attempt) {
            $this->post(route('two-factor.challenge'), ['code' => $wrongCode]);
        }

        $this->post(route('two-factor.challenge'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_new_code_can_be_requested_and_replaces_the_old_one(): void
    {
        $user = User::factory()->create();
        $oldCode = $this->startChallenge($user);

        $this->post(route('two-factor.resend'))->assertSessionHas('status');

        Notification::assertSentToTimes($user, TwoFactorCode::class, 2);
        $newCode = Notification::sent($user, TwoFactorCode::class)->last()->code;

        $this->assertTrue($user->fresh()->hasValidTwoFactorCode($newCode));
        if ($newCode !== $oldCode) {
            $this->assertFalse($user->fresh()->hasValidTwoFactorCode($oldCode));
        }
    }

    public function test_access_revoked_during_the_challenge_blocks_the_login(): void
    {
        $user = User::factory()->create();
        $code = $this->startChallenge($user);
        $user->revokeApproval();

        $this->post(route('two-factor.challenge'), ['code' => $code])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
