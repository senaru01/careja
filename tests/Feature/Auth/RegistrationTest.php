<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_are_registered_as_active_job_seekers(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertTrue($user->isApproved());
    }

    public function test_new_job_seekers_are_not_candidates_until_they_pay(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(0, User::activeCandidates()->count());
    }

    public function test_registration_can_not_choose_a_privileged_role(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'super_admin',
            'organization_id' => 1,
        ]);

        $user = User::where('email', 'penyusup@example.com')->firstOrFail();
        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertNull($user->organization_id);
    }
}
