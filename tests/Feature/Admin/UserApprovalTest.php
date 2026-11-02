<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
