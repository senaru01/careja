<?php

namespace Tests\Feature\Recruitment;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateListTest extends TestCase
{
    use RefreshDatabase;

    public function test_partners_see_only_paid_candidates(): void
    {
        $active = User::factory()->activeCandidate()->create(['name' => 'Kandidat Lunas']);
        User::factory()->create(['name' => 'Belum Bayar']);

        foreach ([User::factory()->lpk()->create(), User::factory()->company()->create(), User::factory()->superAdmin()->create()] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('candidates.index'))
                ->assertOk()
                ->assertSee('Kandidat Lunas')
                ->assertDontSee('Belum Bayar');

            $this->actingAs($viewer)->get(route('candidates.show', $active))->assertOk();
        }
    }

    public function test_unpaid_candidate_profile_is_hidden_from_partners(): void
    {
        $unpaid = User::factory()->create();

        $this->actingAs(User::factory()->company()->create())
            ->get(route('candidates.show', $unpaid))
            ->assertNotFound();
    }

    public function test_workers_can_not_browse_other_candidates(): void
    {
        $this->actingAs(User::factory()->activeCandidate()->create())
            ->get(route('candidates.index'))
            ->assertForbidden();
    }

    public function test_candidates_can_be_filtered_by_theory_result(): void
    {
        ExamAttempt::factory()->passed()->for(User::factory()->activeCandidate()->create(['name' => 'Lulus Teori']))->create();
        ExamAttempt::factory()->failed()->for(User::factory()->activeCandidate()->create(['name' => 'Gagal Teori']))->create();

        $this->actingAs(User::factory()->company()->create())
            ->get(route('candidates.index', ['theory' => 'passed']))
            ->assertSee('Lulus Teori')
            ->assertDontSee('Gagal Teori');
    }

    public function test_test_results_are_visible_to_the_company(): void
    {
        $attempt = ExamAttempt::factory()->passed(88)->create();

        $this->actingAs(User::factory()->company()->create())
            ->get(route('candidates.show', $attempt->user))
            ->assertSee('Nilai <strong>88</strong>', false);
    }
}
