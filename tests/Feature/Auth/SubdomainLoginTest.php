<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Services\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every LPK and company logs in on its own subdomain; the super admin on the root domain.
 */
class SubdomainLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['careja.two_factor_enabled' => false]);
    }

    private function logIn(string $url, User $user): void
    {
        $this->post($url, ['email' => $user->email, 'password' => 'password']);
    }

    public function test_members_and_staff_log_in_on_their_own_subdomain(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        $company = Organization::factory()->company()->create();

        foreach ([User::factory()->memberOf($lpk)->create(), User::factory()->lpk($lpk)->create()] as $user) {
            $this->logIn($lpk->url('login'), $user);
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }

        $staff = User::factory()->company($company)->create();
        $this->logIn($company->url('login'), $staff);
        $this->assertAuthenticatedAs($staff);
    }

    public function test_accounts_can_not_log_in_on_another_subdomain_and_are_told_where_to_go(): void
    {
        $lpk = Organization::factory()->lpk()->create(['subdomain' => 'oki']);
        $otherLpk = Organization::factory()->lpk()->create();
        $member = User::factory()->memberOf($lpk)->create();

        $this->post($otherLpk->url('login'), ['email' => $member->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun ini tidak terdaftar di sini. Silakan login melalui oki.'.config('careja.root_domain').'.']);

        $this->assertGuest();
    }

    public function test_only_the_super_admin_logs_in_on_the_root_domain(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        $admin = User::factory()->superAdmin()->create();
        $rootLogin = app(Tenancy::class)->url(null, 'login');

        $this->logIn($rootLogin, User::factory()->lpk($lpk)->create());
        $this->assertGuest();

        $this->logIn($lpk->url('login'), $admin);
        $this->assertGuest();

        $this->logIn($rootLogin, $admin);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_page_shows_the_organization_of_the_subdomain(): void
    {
        $company = Organization::factory()->company()->create(['name' => 'PT STEP']);

        $this->get($company->url('login'))
            ->assertOk()
            ->assertSee('Login untuk akun PT STEP')
            ->assertDontSee('Daftar sebagai pencari kerja');
    }
}
