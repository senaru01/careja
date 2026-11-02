<?php

namespace Tests\Feature\Recruitment;

use App\Enums\InterviewStatus;
use App\Enums\MedicalStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Interview;
use App\Models\User;
use App\Notifications\InterviewScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    private Exam $exam;

    private User $company;

    /**
     * @var Collection<int, ExamAttempt> passed attempts, best first
     */
    private Collection $passed;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->exam = Exam::factory()->finished()->withQuestions()->create(['quota' => 2]);
        $this->company = User::factory()->company($this->exam->company)->create();
        $this->passed = collect([95, 90, 85, 80])->map(fn (int $score) => ExamAttempt::factory()->for($this->exam)->passed($score)->create());
        ExamAttempt::factory()->for($this->exam)->failed()->create();
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<string, mixed>
     */
    private function invite(array $userIds, ?string $skipReason = null): array
    {
        return [
            'user_ids' => $userIds,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'location' => 'Kantor pusat, Jl. Industri 5',
            'skip_reason' => $skipReason,
        ];
    }

    private function userId(int $rank): int
    {
        return $this->passed[$rank - 1]->user_id;
    }

    public function test_ranking_orders_passed_participants_by_score_then_speed(): void
    {
        $tie = ExamAttempt::factory()->for($this->exam)->passed(95)->create([
            'started_at' => now()->subMinutes(10), 'submitted_at' => now()->subMinutes(5),
        ]);

        $ranking = $this->exam->ranking();

        $this->assertSame([$tie->user_id, $this->userId(1), $this->userId(2), $this->userId(3), $this->userId(4)], $ranking->pluck('user_id')->all());
        $this->assertSame([1, 2, 3, 4, 5], $ranking->pluck('rank')->all());
    }

    public function test_company_invites_the_top_candidates(): void
    {
        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1), $this->userId(2)]))
            ->assertSessionHasNoErrors();

        $interview = Interview::where('user_id', $this->userId(1))->firstOrFail();
        $this->assertTrue($interview->exam->is($this->exam));
        $this->assertSame(1, $interview->exam_rank);
        $this->assertNull($interview->skip_reason);
        $this->assertSame(0, $this->exam->openSeats());
        Notification::assertSentTo(User::find($this->userId(1)), InterviewScheduled::class);
    }

    public function test_selection_waits_until_the_session_has_ended(): void
    {
        $this->exam->update(['ends_at' => now()->addHour()]);

        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1)]))
            ->assertForbidden();
    }

    public function test_company_can_not_invite_more_than_the_quota(): void
    {
        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1), $this->userId(2), $this->userId(3)]))
            ->assertSessionHasErrors('user_ids');

        $this->assertSame(0, Interview::count());
    }

    public function test_skipping_a_better_ranked_candidate_requires_a_reason(): void
    {
        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1), $this->userId(3)]))
            ->assertSessionHasErrors('skip_reason');

        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1), $this->userId(3)], 'Peringkat 2 ahli mesin bubut, kami butuh juru las.'))
            ->assertSessionHasNoErrors();

        $interview = Interview::where('user_id', $this->userId(3))->firstOrFail();
        $this->assertSame('Melewati peringkat 2: Peringkat 2 ahli mesin bubut, kami butuh juru las.', $interview->skip_reason);

        // The skipped candidate sees why.
        $this->actingAs(User::find($this->userId(2)))
            ->get(route('worker.sessions.show', $this->exam))
            ->assertSee('Dilewati')
            ->assertSee('kami butuh juru las');
    }

    public function test_failed_participant_can_not_be_invited(): void
    {
        $failed = $this->exam->attempts()->where('passed', false)->firstOrFail();

        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$failed->user_id]))
            ->assertSessionHasErrors('user_ids');
    }

    public function test_other_company_can_not_select_from_the_session(): void
    {
        $this->actingAs(User::factory()->company()->create())
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1)]))
            ->assertForbidden();
    }

    public function test_lpk_can_not_schedule_interviews(): void
    {
        $this->actingAs(User::factory()->lpk($this->exam->organization)->create())
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1)]))
            ->assertForbidden();
    }

    public function test_company_records_the_interview_result_once(): void
    {
        $interview = Interview::factory()->for($this->company->organization)->create();

        $this->actingAs($this->company)->put(route('company.interviews.update', $interview), [
            'status' => 'passed',
            'score' => 85,
            'evaluation' => 'Komunikasi baik, perlu meningkatkan bahasa Jepang.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->company)->put(route('company.interviews.update', $interview), [
            'status' => 'failed',
            'score' => 10,
            'evaluation' => 'Mencoba mengubah hasil wawancara.',
        ])->assertForbidden();

        $interview->refresh();
        $this->assertSame(InterviewStatus::Passed, $interview->status);
        $this->assertSame(85, $interview->score);
        $this->assertSame('Tahap Medical Checkup', $interview->user->candidateStage());
    }

    public function test_other_company_can_not_record_the_result(): void
    {
        $interview = Interview::factory()->create();

        $this->actingAs(User::factory()->company()->create())
            ->put(route('company.interviews.update', $interview), [
                'status' => 'passed', 'score' => 90, 'evaluation' => 'Bukan perusahaan yang mengundang.',
            ])
            ->assertForbidden();

        $this->assertSame(InterviewStatus::Scheduled, $interview->fresh()->status);
    }

    public function test_medical_checkup_is_recorded_once_after_a_passed_interview(): void
    {
        $interview = Interview::factory()->for($this->company->organization)->create(['status' => InterviewStatus::Passed]);

        $this->actingAs($this->company)->put(route('company.interviews.medical', $interview), [
            'medical_status' => 'fit',
            'medical_notes' => 'Klinik Sehat, 2 Nov: semua parameter normal.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->company)->put(route('company.interviews.medical', $interview), [
            'medical_status' => 'unfit',
            'medical_notes' => 'Mencoba mengubah hasil.',
        ])->assertForbidden();

        $interview->refresh();
        $this->assertSame(MedicalStatus::Fit, $interview->medical_status);
        $this->assertSame('Diterima Perusahaan', $interview->user->candidateStage());
    }

    public function test_medical_is_not_possible_before_the_interview_is_passed(): void
    {
        $interview = Interview::factory()->for($this->company->organization)->create();

        $this->actingAs($this->company)->put(route('company.interviews.medical', $interview), [
            'medical_status' => 'fit', 'medical_notes' => 'Belum wawancara.',
        ])->assertForbidden();
    }

    public function test_unfit_candidate_frees_a_seat_for_a_replacement_from_the_session(): void
    {
        $this->actingAs($this->company)->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(1), $this->userId(2)]));
        $first = Interview::where('user_id', $this->userId(1))->firstOrFail();
        $first->forceFill(['status' => InterviewStatus::Passed])->save();

        $this->actingAs($this->company)->put(route('company.interviews.medical', $first), [
            'medical_status' => 'unfit',
            'medical_notes' => 'Ditemukan gangguan penglihatan berat.',
        ]);

        $this->assertSame(1, $this->exam->openSeats());

        // Rank 4 is picked over rank 3, so a reason is needed again.
        $this->actingAs($this->company)
            ->post(route('company.interviews.store', $this->exam), $this->invite([$this->userId(4)], 'Peringkat 3 sudah diterima di perusahaan lain.'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $this->exam->openSeats());
        $this->assertSame('Tidak Lolos Medical', User::find($this->userId(1))->candidateStage());
    }

    public function test_company_sees_the_ranking_of_its_finished_session(): void
    {
        $this->actingAs($this->company)
            ->get(route('company.sessions.show', $this->exam))
            ->assertOk()
            ->assertSee('Peringkat Final')
            ->assertSee(User::find($this->userId(1))->name);
    }

    public function test_workers_see_the_ranking_without_other_names(): void
    {
        $me = User::find($this->userId(3));

        $this->actingAs($me)
            ->get(route('worker.sessions.show', $this->exam))
            ->assertOk()
            ->assertSee('Peringkat Anda: <strong>#3</strong>', false)
            ->assertDontSee(User::find($this->userId(1))->name);
    }
}
