<?php

namespace Tests\Feature\Recruitment;

use App\Models\Exam;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Notifications\RecruitmentRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecruitmentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestData(): array
    {
        return [
            'title' => 'Rekrutmen Produksi Desember',
            'needed_by' => now()->addMonth()->format('Y-m-d'),
            'description' => 'Sistem 3 shift.',
            'positions' => [
                ['position' => 'Operator', 'quota' => 2, 'gender' => 'male', 'min_education' => 'sma', 'min_age' => 18, 'max_age' => 30, 'min_experience_years' => 0],
                ['position' => 'Staff QC', 'quota' => 1, 'min_education' => 'd3', 'min_age' => '', 'max_age' => '', 'min_experience_years' => 2],
                ['position' => 'Admin Produksi', 'quota' => 1, 'min_education' => '', 'min_age' => '', 'max_age' => 35, 'min_experience_years' => 0],
                ['position' => '', 'quota' => '', 'min_education' => '', 'min_age' => '', 'max_age' => '', 'min_experience_years' => 0],
            ],
        ];
    }

    public function test_company_releases_a_request_with_a_position_table_and_lpks_are_told(): void
    {
        $company = User::factory()->company()->create();
        $lpk = User::factory()->lpk()->create();

        $this->actingAs($company)->get(route('company.recruitments.create'))->assertOk();
        $this->actingAs($company)->post(route('company.recruitments.store'), $this->requestData())->assertSessionHasNoErrors();

        $recruitment = RecruitmentRequest::with('positions')->sole();
        $this->assertSame($company->organization_id, $recruitment->company_id);
        $this->assertSame(['Operator', 'Staff QC', 'Admin Produksi'], $recruitment->positions->pluck('position')->all());
        $this->assertSame(4, $recruitment->totalQuota());
        $this->assertSame(2, $recruitment->positions[1]->min_experience_years);
        $this->assertSame('Laki-laki', $recruitment->positions[0]->gender->label());
        $this->assertNull($recruitment->positions[1]->gender);
        $this->assertSame('Menunggu LPK', $recruitment->statusLabel());
        Notification::assertSentTo($lpk, RecruitmentRequested::class);

        $this->actingAs($company)->get(route('company.recruitments.show', $recruitment))->assertOk()->assertSee('Staff QC');
        $this->actingAs($company)->get(route('company.recruitments.edit', $recruitment))->assertOk();
        $this->actingAs($company)->get(route('company.recruitments.index'))->assertOk()->assertSee($recruitment->title);
        $this->actingAs($lpk)->get(route('dashboard'))->assertOk()->assertSee($recruitment->title);
    }

    public function test_age_range_must_not_be_reversed(): void
    {
        $data = $this->requestData();
        $data['positions'][0]['min_age'] = 30;
        $data['positions'][0]['max_age'] = 20;

        $this->actingAs(User::factory()->company()->create())
            ->post(route('company.recruitments.store'), $data)
            ->assertSessionHasErrors('positions.0.max_age');
    }

    public function test_company_only_sees_its_own_requests(): void
    {
        $company = User::factory()->company()->create();
        $other = RecruitmentRequest::factory()->withPositions()->create();

        $this->actingAs($company)->get(route('company.recruitments.show', $other))->assertForbidden();
        $this->actingAs($company)->delete(route('company.recruitments.destroy', $other))->assertNotFound();
        $this->assertModelExists($other);
    }

    public function test_lpk_turns_a_request_into_a_session_with_the_same_positions(): void
    {
        $lpk = User::factory()->lpk()->create();
        $recruitment = RecruitmentRequest::factory()->withPositions(2)->create();

        $this->actingAs($lpk)->get(route('lpk.recruitments.index'))->assertOk()->assertSee($recruitment->title);
        $this->actingAs($lpk)->get(route('lpk.exams.create', ['permintaan' => $recruitment->id]))
            ->assertOk()
            ->assertSee($recruitment->positions[0]->position);

        $this->actingAs($lpk)->post(route('lpk.exams.store'), [
            'title' => 'Tes Produksi',
            'company_id' => $recruitment->company_id,
            'recruitment_request_id' => $recruitment->id,
            'positions' => $recruitment->positions->map(fn ($position) => [
                ...$position->only(['position', 'quota', 'min_age', 'max_age', 'min_experience_years']),
                'min_education' => $position->min_education?->value,
            ])->all(),
            'duration_minutes' => 30,
            'passing_score' => 70,
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'),
            'ends_at' => now()->addWeek()->addHours(2)->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();

        $exam = Exam::with('positions')->sole();
        $this->assertTrue($exam->recruitmentRequest->is($recruitment));
        $this->assertSame($recruitment->positions->pluck('position')->all(), $exam->positions->pluck('position')->all());
        $this->assertSame('Ditindaklanjuti LPK', $recruitment->fresh()->statusLabel());
    }

    public function test_session_from_a_request_must_be_for_the_requesting_company(): void
    {
        $lpk = User::factory()->lpk()->create();
        $recruitment = RecruitmentRequest::factory()->withPositions(1)->create();
        $otherCompany = User::factory()->company()->create()->organization;

        $this->actingAs($lpk)->post(route('lpk.exams.store'), [
            'title' => 'Tes',
            'company_id' => $otherCompany->id,
            'recruitment_request_id' => $recruitment->id,
            'positions' => [['position' => 'Operator', 'quota' => 1, 'min_experience_years' => 0]],
            'duration_minutes' => 30,
            'passing_score' => 70,
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'),
            'ends_at' => now()->addWeek()->addHours(2)->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('company_id');
    }

    public function test_request_is_locked_once_an_lpk_made_a_session_and_can_be_closed(): void
    {
        $company = User::factory()->company()->create();
        $recruitment = RecruitmentRequest::factory()->withPositions()->for($company->organization, 'company')->create();
        Exam::factory()->draft()->create(['company_id' => $company->organization_id, 'recruitment_request_id' => $recruitment->id]);

        $this->actingAs($company)->get(route('company.recruitments.edit', $recruitment))->assertForbidden();
        $this->actingAs($company)->patch(route('company.recruitments.close', $recruitment))->assertRedirect();

        $this->assertTrue($recruitment->fresh()->isClosed());
        $this->actingAs(User::factory()->lpk()->create())
            ->get(route('lpk.exams.create', ['permintaan' => $recruitment->id]))
            ->assertForbidden();
    }

    public function test_company_dashboard_shows_its_requests(): void
    {
        $company = User::factory()->company()->create();
        $recruitment = RecruitmentRequest::factory()->withPositions()->for($company->organization, 'company')->create();

        $this->actingAs($company)->get(route('dashboard'))->assertOk()->assertSee($recruitment->title);
    }
}
