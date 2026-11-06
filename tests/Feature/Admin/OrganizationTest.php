<?php

namespace Tests\Feature\Admin;

use App\Enums\OrganizationType;
use App\Enums\PaymentCollector;
use App\Enums\PaymentPurpose;
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

        $response = $this->actingAs($admin)->post(route('admin.organizations.store'), [
            'type' => 'lpk',
            'name' => 'LPK Maju Jaya',
            'subdomain' => 'majujaya',
            'account_name' => 'Budi',
            'account_email' => 'budi@lpk.test',
            'account_password' => 'rahasia123',
        ]);

        $organization = Organization::firstOrFail();
        $account = User::where('email', 'budi@lpk.test')->firstOrFail();

        $response->assertRedirect(route('admin.organizations.edit', $organization));
        $this->assertSame(OrganizationType::Lpk, $organization->type);
        $this->assertSame('majujaya', $organization->subdomain);
        $this->assertSame(UserRole::Lpk, $account->role);
        $this->assertTrue($account->organization->is($organization));
        $this->assertTrue($account->isApproved());
    }

    public function test_subdomain_must_be_unique_lowercase_and_not_reserved(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Organization::factory()->lpk()->create(['subdomain' => 'oki']);

        foreach (['oki', 'PT Step', 'www', 'admin'] as $subdomain) {
            $this->actingAs($admin)->post(route('admin.organizations.store'), [
                'type' => 'company',
                'name' => 'PT Step',
                'subdomain' => $subdomain,
                'account_name' => 'X',
                'account_email' => 'x@pt.test',
                'account_password' => 'rahasia123',
            ])->assertSessionHasErrors('subdomain');
        }

        $this->assertSame(1, Organization::count());
    }

    public function test_super_admin_sets_the_fees_and_payment_settings_of_an_lpk(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lpk = Organization::factory()->lpk()->create();

        $this->actingAs($admin)->get(route('admin.organizations.edit', $lpk))->assertOk()->assertSee('Biaya per Pos');

        $this->actingAs($admin)->put(route('admin.organizations.update', $lpk), [
            'name' => $lpk->name,
            'subdomain' => 'oki',
            'membership_fee' => 75000,
            'exam_registration_fee' => 10000,
            'membership_collected_by' => 'lpk',
            'careja_share_percent' => 20,
        ])->assertSessionHasErrors(['bank_name', 'bank_account_number', 'bank_account_holder']);

        $this->actingAs($admin)->put(route('admin.organizations.update', $lpk), [
            'name' => $lpk->name,
            'subdomain' => 'oki',
            'membership_fee' => 75000,
            'exam_registration_fee' => 10000,
            'membership_collected_by' => 'lpk',
            'careja_share_percent' => 20,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'PT OKI',
        ])->assertRedirect(route('admin.organizations.index'));

        $lpk->refresh();
        $this->assertSame('oki', $lpk->subdomain);
        $this->assertSame(75000, $lpk->feeFor(PaymentPurpose::Membership));
        $this->assertSame(10000, $lpk->feeFor(PaymentPurpose::ExamRegistration));
        // Posts left empty keep the default price.
        $this->assertSame(config('careja.exam_session_fee'), $lpk->feeFor(PaymentPurpose::ExamSession));
        $this->assertSame(PaymentCollector::Lpk, $lpk->membershipCollector());
        $this->assertSame(15000, $lpk->carejaShareOf(75000));

        $this->actingAs($admin)->get(route('admin.organizations.index'))->assertOk()->assertSee('oki.'.config('careja.root_domain'));
    }

    public function test_super_admin_connects_a_company_to_lpks(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $company = Organization::factory()->company()->create();
        [$first, $second, $third] = Organization::factory()->lpk()->count(3)->create();

        $this->actingAs($admin)->put(route('admin.organizations.update', $company), [
            'name' => $company->name,
            'subdomain' => 'step',
            'lpk_ids' => [$first->id, $third->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$first->id, $third->id], $company->connectedLpks()->pluck('organizations.id')->all());
        $this->assertTrue($first->isConnectedTo($company));
        $this->assertFalse($second->isConnectedTo($company));

        $this->actingAs($admin)->put(route('admin.organizations.update', $company), [
            'name' => $company->name,
            'subdomain' => 'step',
        ]);

        $this->assertSame(0, $company->connectedLpks()->count());
    }

    public function test_only_super_admin_can_create_organizations(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.organizations.store'), [
            'type' => 'company',
            'name' => 'PT Palsu',
            'subdomain' => 'palsu',
            'account_name' => 'X',
            'account_email' => 'x@pt.test',
            'account_password' => 'rahasia123',
        ])->assertForbidden();

        $this->assertSame(0, Organization::count());
    }
}
