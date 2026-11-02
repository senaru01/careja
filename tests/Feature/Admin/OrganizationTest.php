<?php

namespace Tests\Feature\Admin;

use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_creates_an_lpk_with_its_login_account(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.organizations.store'), [
            'type' => 'lpk',
            'name' => 'LPK Maju Jaya',
            'account_name' => 'Budi',
            'account_email' => 'budi@lpk.test',
            'account_password' => 'rahasia123',
        ])->assertRedirect(route('admin.organizations.index'));

        $organization = Organization::firstOrFail();
        $account = User::where('email', 'budi@lpk.test')->firstOrFail();

        $this->assertSame(OrganizationType::Lpk, $organization->type);
        $this->assertSame(UserRole::Lpk, $account->role);
        $this->assertTrue($account->organization->is($organization));
        $this->assertTrue($account->isApproved());
    }

    public function test_only_super_admin_can_create_organizations(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.organizations.store'), [
            'type' => 'company',
            'name' => 'PT Palsu',
            'account_name' => 'X',
            'account_email' => 'x@pt.test',
            'account_password' => 'rahasia123',
        ])->assertForbidden();

        $this->assertSame(0, Organization::count());
    }
}
