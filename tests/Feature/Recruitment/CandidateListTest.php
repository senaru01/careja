<?php

namespace Tests\Feature\Recruitment;

use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateListTest extends TestCase
{
    use RefreshDatabase;

    private Organization $lpk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lpk = Organization::factory()->lpk()->create();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->memberOf($this->lpk)->activeCandidate()->create($attributes);
    }

    private function connectedCompanyStaff(): User
    {
        return User::factory()->company(Organization::factory()->connectedTo($this->lpk)->create())->create();
    }

    public function test_partners_see_only_paid_candidates(): void
    {
        $active = $this->member(['name' => 'Kandidat Lunas']);
        User::factory()->memberOf($this->lpk)->create(['name' => 'Belum Bayar']);

        foreach ([User::factory()->lpk($this->lpk)->create(), $this->connectedCompanyStaff(), User::factory()->superAdmin()->create()] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('candidates.index'))
                ->assertOk()
                ->assertSee('Kandidat Lunas')
                ->assertDontSee('Belum Bayar');

            $this->actingAs($viewer)->get(route('candidates.show', $active))->assertOk();
        }
    }

    public function test_lpk_sees_only_its_own_members(): void
    {
        $this->member(['name' => 'Member Sendiri']);
        $outsider = User::factory()->memberOf(Organization::factory()->lpk()->create())->activeCandidate()->create(['name' => 'Member LPK Lain']);
        $staff = User::factory()->lpk($this->lpk)->create();

        $this->actingAs($staff)->get(route('candidates.index'))
            ->assertSee('Member Sendiri')
            ->assertDontSee('Member LPK Lain');
        $this->actingAs($staff)->get(route('candidates.show', $outsider))->assertNotFound();

        $this->actingAs($staff)->get(route('dashboard'))->assertDontSee('Member LPK Lain');
    }

    public function test_company_sees_members_of_every_connected_lpk_only(): void
    {
        $secondLpk = Organization::factory()->lpk()->create(['name' => 'LPK Kedua']);
        $company = Organization::factory()->connectedTo([$this->lpk, $secondLpk])->create();
        $staff = User::factory()->company($company)->create();

        $this->member(['name' => 'Member Pertama']);
        $second = User::factory()->memberOf($secondLpk)->activeCandidate()->create(['name' => 'Member Kedua']);
        $outsider = User::factory()->memberOf(Organization::factory()->lpk()->create())->activeCandidate()->create(['name' => 'Member Tak Terhubung']);

        $this->actingAs($staff)->get(route('candidates.index'))
            ->assertSee('Member Pertama')
            ->assertSee('Member Kedua')
            ->assertDontSee('Member Tak Terhubung');

        $this->actingAs($staff)->get(route('candidates.index', ['lpk' => $secondLpk->id]))
            ->assertSee('Member Kedua')
            ->assertDontSee('Member Pertama');

        $this->actingAs($staff)->get(route('candidates.show', $second))->assertOk();
        $this->actingAs($staff)->get(route('candidates.show', $outsider))->assertNotFound();
    }

    public function test_list_shows_age_experience_and_contact_and_filters_on_them(): void
    {
        $experienced = $this->member(['name' => 'Budi Berpengalaman']);
        $experienced->candidateProfile->update(['birth_date' => now()->subYears(32)->subMonth(), 'phone' => '0812-3456-7890']);
        WorkExperience::factory()->for($experienced)->create([
            'position' => 'Operator Las',
            'start_date' => now()->subYears(3),
            'end_date' => now()->subYear(),
        ]);
        WorkExperience::factory()->for($experienced)->create([
            'position' => 'Staff QC',
            'start_date' => now()->subYears(2),
            'end_date' => null,
        ]);
        $fresh = $this->member(['name' => 'Fresh Graduate Siti']);
        $fresh->candidateProfile->update(['birth_date' => now()->subYears(19)->subMonth()]);

        $lpk = User::factory()->lpk($this->lpk)->create();

        // Overlapping jobs count once: three years in total.
        $this->assertSame(3.0, $experienced->workExperienceYears());

        $this->actingAs($lpk)->get(route('candidates.index'))
            ->assertOk()
            ->assertSee('32 tahun')
            ->assertSee('3 tahun')
            ->assertSee('Operator Las')
            ->assertSee('https://wa.me/6281234567890');

        $this->actingAs($lpk)->get(route('candidates.index', ['experience' => 'fresh']))
            ->assertSee('Fresh Graduate Siti')
            ->assertDontSee('Budi Berpengalaman');

        $this->actingAs($lpk)->get(route('candidates.index', ['min_age' => 25, 'max_age' => 35]))
            ->assertSee('Budi Berpengalaman')
            ->assertDontSee('Fresh Graduate Siti');
    }

    public function test_lpk_dashboard_lists_active_candidates_with_contact(): void
    {
        $candidate = $this->member(['name' => 'Kandidat Belum Tes']);

        $this->actingAs(User::factory()->lpk($this->lpk)->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kandidat Belum Tes')
            ->assertSee($candidate->candidateProfile->whatsappUrl());
    }

    public function test_unpaid_candidate_profile_is_hidden_from_partners(): void
    {
        $unpaid = User::factory()->memberOf($this->lpk)->create();

        $this->actingAs($this->connectedCompanyStaff())
            ->get(route('candidates.show', $unpaid))
            ->assertNotFound();
    }

    public function test_workers_can_not_browse_other_candidates(): void
    {
        $this->actingAs($this->member())
            ->get(route('candidates.index'))
            ->assertForbidden();
    }

    public function test_candidates_can_be_filtered_by_theory_result(): void
    {
        ExamAttempt::factory()->passed()->for($this->member(['name' => 'Lulus Teori']))->create();
        ExamAttempt::factory()->failed()->for($this->member(['name' => 'Gagal Teori']))->create();

        $this->actingAs($this->connectedCompanyStaff())
            ->get(route('candidates.index', ['theory' => 'passed']))
            ->assertSee('Lulus Teori')
            ->assertDontSee('Gagal Teori');
    }

    public function test_test_results_are_visible_to_the_company(): void
    {
        $attempt = ExamAttempt::factory()->passed(88)->for($this->member())->create();

        $this->actingAs($this->connectedCompanyStaff())
            ->get(route('candidates.show', $attempt->user))
            ->assertSee('Nilai <strong>88</strong>', false);
    }
}
