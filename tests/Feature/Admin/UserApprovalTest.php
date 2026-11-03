<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_see_pending_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($pending->email);
    }

    public function test_regular_users_can_not_access_user_management(): void
    {
        $user = User::factory()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.users.approve', $pending))->assertForbidden();

        $this->assertFalse($pending->fresh()->isApproved());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_super_admin_can_approve_a_pending_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.approve', $pending))
            ->assertSessionHas('status');

        $pending->refresh();
        $this->assertTrue($pending->isApproved());
        $this->assertTrue($pending->approver->is($superAdmin));
    }

    public function test_super_admin_can_revoke_a_users_access(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)->patch(route('admin.users.revoke', $user));

        $this->assertFalse($user->fresh()->isApproved());
    }

    public function test_super_admin_access_can_not_be_revoked(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.revoke', $otherSuperAdmin))
            ->assertSessionHasErrors('user');

        $this->assertTrue($otherSuperAdmin->fresh()->isApproved());
    }

    public function test_super_admin_can_reject_a_pending_registration(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $pending));

        $this->assertModelMissing($pending);
    }

    public function test_approved_users_can_not_be_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $user))
            ->assertSessionHasErrors('user');

        $this->assertModelExists($user);
    }

    public function test_super_admin_resets_a_users_password_to_a_temporary_one(): void
    {
        config(['session.driver' => 'database']);
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create(['password' => 'password-lama']);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        $response = $this->actingAs($superAdmin)->patch(route('admin.users.password', $user));

        $temporaryPassword = session('temporaryPassword');
        $response->assertSessionHasNoErrors()->assertSessionHas('temporaryPassword.email', $user->email);
        $this->assertSame(10, strlen($temporaryPassword['password']));
        $this->assertTrue(Hash::check($temporaryPassword['password'], $user->fresh()->password));
        $this->assertFalse(Hash::check('password-lama', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_temporary_password_is_shown_once_on_the_user_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)->patch(route('admin.users.password', $user));
        $password = session('temporaryPassword')['password'];

        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertSee($password);
        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertDontSee($password);
    }

    public function test_super_admin_can_not_reset_their_own_password_here(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['password' => 'password-saya']);

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.password', $superAdmin))
            ->assertSessionHasErrors(['user' => 'Ubah password Anda sendiri lewat menu Profil.']);

        $this->assertTrue(Hash::check('password-saya', $superAdmin->fresh()->password));
    }

    public function test_regular_users_can_not_reset_passwords(): void
    {
        $victim = User::factory()->create(['password' => 'password-asli']);

        $this->actingAs(User::factory()->lpk()->create())
            ->patch(route('admin.users.password', $victim))
            ->assertForbidden();

        $this->assertTrue(Hash::check('password-asli', $victim->fresh()->password));
    }
}
