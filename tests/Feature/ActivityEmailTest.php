<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_emails_are_not_sent_when_switched_off_but_login_codes_are(): void
    {
        config(['careja.activity_emails' => false, 'careja.two_factor_enabled' => true]);
        Notification::fake();

        $lpk = User::factory()->lpk()->create();
        $company = User::factory()->company(Organization::factory()->connectedTo($lpk->organization)->create())->create();

        $this->actingAs($company)->post(route('company.recruitments.store'), [
            'title' => 'Rekrutmen Produksi',
            'lpk_ids' => [$lpk->organization_id],
            'positions' => [['position' => 'Operator', 'quota' => 2, 'min_experience_years' => 0]],
        ])->assertSessionHasNoErrors();

        Notification::assertNothingSent();

        auth()->logout();
        $this->post($company->organization->url('login'), ['email' => $company->email, 'password' => 'password']);

        Notification::assertSentTo($company, TwoFactorCode::class);
    }
}
