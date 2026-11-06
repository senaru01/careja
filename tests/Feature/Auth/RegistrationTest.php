<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function form(string $email = 'test@example.com'): array
    {
        return [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }

    public function test_registration_screen_on_an_lpk_subdomain_shows_the_lpk(): void
    {
        $lpk = Organization::factory()->lpk()->create(['name' => 'LPK OKI']);

        $this->get($lpk->url('register'))
            ->assertOk()
            ->assertSee('member LPK OKI');
    }

    public function test_root_domain_lists_partner_lpks_instead_of_the_form(): void
    {
        $lpk = Organization::factory()->lpk()->create(['name' => 'LPK OKI', 'subdomain' => 'oki']);

        $this->get('/register')
            ->assertOk()
            ->assertSee('LPK OKI')
            ->assertSee($lpk->url('register'), false)
            ->assertDontSee('name="password"', false);
    }

    public function test_new_users_become_active_members_of_the_lpk_of_the_subdomain(): void
    {
        $lpk = Organization::factory()->lpk()->create();

        $response = $this->post($lpk->url('register'), $this->form());

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertTrue($user->isApproved());
        $this->assertSame($lpk->id, $user->organization_id);
    }

    public function test_registration_is_closed_on_the_root_domain_and_company_subdomains(): void
    {
        $company = Organization::factory()->company()->create();

        $this->post('/register', $this->form('a@example.com'))->assertNotFound();
        $this->post($company->url('register'), $this->form('b@example.com'))->assertNotFound();

        $this->assertSame(0, User::count());
    }

    public function test_one_email_can_only_be_a_member_of_one_lpk(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        User::factory()->memberOf($lpk)->create(['email' => 'test@example.com']);
        $otherLpk = Organization::factory()->lpk()->create();

        $this->post($otherLpk->url('register'), $this->form())->assertSessionHasErrors('email');

        $this->assertSame($lpk->id, User::where('email', 'test@example.com')->value('organization_id'));
    }

    public function test_unknown_subdomain_does_not_exist(): void
    {
        $this->get('http://tidakada.'.config('careja.root_domain').'/register')->assertNotFound();
    }

    public function test_new_job_seekers_are_not_candidates_until_they_pay(): void
    {
        $lpk = Organization::factory()->lpk()->create();

        $this->post($lpk->url('register'), $this->form());

        $this->assertSame(0, User::activeCandidates()->count());
    }

    public function test_registration_can_not_choose_a_privileged_role_or_another_lpk(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        $otherLpk = Organization::factory()->lpk()->create();

        $this->post($lpk->url('register'), [
            ...$this->form('penyusup@example.com'),
            'role' => 'super_admin',
            'organization_id' => $otherLpk->id,
        ]);

        $user = User::where('email', 'penyusup@example.com')->firstOrFail();
        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertSame($lpk->id, $user->organization_id);
    }
}
