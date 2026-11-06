<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_two_factor_disabled_logs_in_directly(): void
    {
        Notification::fake();
        $user = User::factory()->superAdmin()->create(['two_factor_enabled' => false]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        Notification::assertNothingSent();
    }

    public function test_global_switch_turns_two_factor_off_for_everyone(): void
    {
        Notification::fake();
        config(['careja.two_factor_enabled' => false]);
        $user = User::factory()->superAdmin()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_pending_users_are_still_blocked_when_two_factor_is_off(): void
    {
        config(['careja.two_factor_enabled' => false]);
        $user = User::factory()->pending()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_still_rejected_when_two_factor_is_off(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah']);

        $this->assertGuest();
    }

    public function test_super_admin_can_toggle_two_factor_per_user(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->patch(route('admin.users.two-factor', $user))->assertSessionHas('status');
        $this->assertFalse($user->fresh()->two_factor_enabled);

        $this->actingAs($admin)->patch(route('admin.users.two-factor', $user));
        $this->assertTrue($user->fresh()->two_factor_enabled);

        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
        Notification::assertSentTo($user, TwoFactorCode::class);
    }

    public function test_only_super_admin_can_toggle_two_factor(): void
    {
        $user = User::factory()->create();

        foreach ([$user, User::factory()->lpk()->create(), User::factory()->company()->create()] as $actor) {
            $this->actingAs($actor)->patch(route('admin.users.two-factor', $user))->assertForbidden();
        }

        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_users_page_shows_the_setting_and_the_global_warning(): void
    {
        config(['careja.two_factor_enabled' => false]);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Verifikasi 2 Langkah')
            ->assertSee('dimatikan untuk semua pengguna', false);
    }
}
