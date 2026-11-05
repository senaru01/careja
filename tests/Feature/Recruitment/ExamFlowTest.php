<?php

namespace Tests\Feature\Recruitment;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, int> question id => option id
     */
    private function answers(Exam $exam, bool $correct): array
    {
        return $exam->questions()->with('options')->get()
            ->mapWithKeys(fn ($question) => [
                $question->id => $question->options->firstWhere('is_correct', $correct)->id,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionData(Organization $company): array
    {
        return [
            'title' => 'Tes Dasar Pengelasan',
            'company_id' => $company->id,
            'positions' => [
                ['position' => 'Juru Las SMAW', 'quota' => 7, 'min_education' => 'smk', 'min_age' => 18, 'max_age' => 30, 'min_experience_years' => 0],
                ['position' => 'Staff QC', 'quota' => 3, 'min_education' => 'd3', 'min_age' => '', 'max_age' => '', 'min_experience_years' => 2],
                ['position' => '', 'quota' => '', 'min_education' => '', 'min_age' => '', 'max_age' => '', 'min_experience_years' => 0],
            ],
            'qualifications' => 'Sertifikat BNSP diutamakan.',
            'min_participants' => 20,
            'duration_minutes' => 30,
            'passing_score' => 70,
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'),
            'ends_at' => now()->addWeek()->addHours(8)->format('Y-m-d H:i'),
        ];
    }

    public function test_lpk_creates_a_draft_session_for_a_company_and_adds_questions(): void
    {
        $lpk = User::factory()->lpk()->create();
        $company = Organization::factory()->company()->create();

        $this->actingAs($lpk)->get(route('lpk.exams.create'))->assertOk();
        $this->actingAs($lpk)->post(route('lpk.exams.store'), $this->sessionData($company))->assertSessionHasNoErrors();
        $exam = Exam::firstOrFail();
        $this->actingAs($lpk)->get(route('lpk.exams.edit', $exam))->assertOk()->assertSee('Staff QC');
        $this->actingAs($lpk)->get(route('lpk.exams.show', $exam))->assertOk()->assertSee('Pengalaman min. 2 tahun');

        $this->actingAs($lpk)->post(route('lpk.exams.questions.store', $exam), [
            'question' => 'Alat pelindung mata saat mengelas?',
            'options' => ['Kacamata las', 'Topi', '', ''],
            'correct_option' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($exam->organization->is($lpk->organization));
        $this->assertTrue($exam->company->is($company));
        $this->assertSame(10, $exam->quota);
        $this->assertSame('Juru Las SMAW, Staff QC', $exam->position);
        $this->assertSame([7, 3], $exam->positions->pluck('quota')->all());
        $this->assertSame(2, $exam->positions[1]->min_experience_years);
        $this->assertFalse($exam->isPublished());
        $question = $exam->questions()->with('options')->firstOrFail();
        $this->assertCount(2, $question->options);
        $this->assertTrue($question->options->firstWhere('text', 'Kacamata las')->is_correct);
    }

    public function test_session_must_be_for_a_company_not_an_lpk(): void
    {
        $lpk = User::factory()->lpk()->create();

        $this->actingAs($lpk)
            ->post(route('lpk.exams.store'), $this->sessionData($lpk->organization))
            ->assertSessionHasErrors('company_id');
    }

    public function test_correct_option_must_point_to_a_filled_option(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->create();

        $this->actingAs($lpk)->post(route('lpk.exams.questions.store', $exam), [
            'question' => 'Pertanyaan?',
            'options' => ['A', 'B', '', ''],
            'correct_option' => 3,
        ])->assertSessionHasErrors('correct_option');
    }

    public function test_lpk_can_not_see_or_change_another_lpks_exam(): void
    {
        $lpk = User::factory()->lpk()->create();
        $otherExam = Exam::factory()->draft()->create();

        $this->actingAs($lpk)->get(route('lpk.exams.show', $otherExam))->assertForbidden();
        $this->actingAs($lpk)->delete(route('lpk.exams.destroy', $otherExam))->assertForbidden();
        $this->assertModelExists($otherExam);
    }

    public function test_published_session_is_locked(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->upcoming()->withQuestions()->create();

        $this->actingAs($lpk)->post(route('lpk.exams.questions.store', $exam), [
            'question' => 'Soal susulan?',
            'options' => ['A', 'B'],
            'correct_option' => 0,
        ])->assertForbidden();

        $this->actingAs($lpk)
            ->put(route('lpk.exams.update', $exam), ['passing_score' => 10] + $this->sessionData($exam->company))
            ->assertForbidden();

        $this->actingAs($lpk)->delete(route('lpk.exams.destroy', $exam))->assertForbidden();

        $this->assertSame(70, $exam->fresh()->passing_score);
    }

    public function test_candidate_takes_the_exam_and_is_graded_automatically(): void
    {
        $attempt = ExamAttempt::factory()->create();
        $candidate = $attempt->user;

        $this->actingAs($candidate)->post(route('worker.attempts.start', $attempt));
        $this->actingAs($candidate)
            ->post(route('worker.attempts.submit', $attempt), ['answers' => $this->answers($attempt->exam, true)])
            ->assertRedirect(route('worker.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(100, $attempt->score);
        $this->assertSame(3, $attempt->correct_count);
        $this->assertTrue($attempt->passed);
        $this->assertTrue($candidate->hasPassedTheory());
    }

    public function test_exam_can_not_be_started_before_the_session_opens(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $attempt = ExamAttempt::factory()->for($exam)->create();

        $this->actingAs($attempt->user)
            ->post(route('worker.attempts.start', $attempt))
            ->assertSessionHasErrors('attempt');

        $this->assertNull($attempt->fresh()->started_at);
    }

    public function test_exam_can_not_be_started_after_the_session_closed(): void
    {
        $exam = Exam::factory()->finished()->withQuestions()->create();
        $attempt = ExamAttempt::factory()->for($exam)->create();

        $this->actingAs($attempt->user)->post(route('worker.attempts.start', $attempt));

        $this->assertNull($attempt->fresh()->started_at);
        $this->assertSame('Tidak Hadir', $attempt->fresh()->statusLabel());
    }

    public function test_timer_never_runs_past_the_session_close(): void
    {
        $exam = Exam::factory()->withQuestions()->create(['ends_at' => now()->addMinutes(10)]);
        $attempt = ExamAttempt::factory()->for($exam)->started()->create();

        $this->assertTrue($attempt->deadline()->equalTo($exam->ends_at));
    }

    public function test_unpaid_seat_can_not_take_the_exam(): void
    {
        $attempt = ExamAttempt::factory()->unpaid()->create();

        $this->actingAs($attempt->user)->post(route('worker.attempts.start', $attempt))->assertForbidden();
    }

    public function test_wrong_answers_fail_the_exam(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();

        $this->actingAs($attempt->user)
            ->post(route('worker.attempts.submit', $attempt), ['answers' => $this->answers($attempt->exam, false)]);

        $attempt->refresh();
        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    public function test_answers_sent_after_the_deadline_are_not_counted(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();

        $this->travel($attempt->exam->duration_minutes + 2)->minutes();

        $this->actingAs($attempt->user)
            ->post(route('worker.attempts.submit', $attempt), ['answers' => $this->answers($attempt->exam, true)]);

        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_a_submitted_exam_can_not_be_resubmitted(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $wrong = $this->answers($attempt->exam, false);
        $correct = $this->answers($attempt->exam, true);

        $this->actingAs($attempt->user)->post(route('worker.attempts.submit', $attempt), ['answers' => $wrong]);
        $this->actingAs($attempt->user)->post(route('worker.attempts.submit', $attempt), ['answers' => $correct]);

        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_option_from_another_question_does_not_count(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $correct = array_values($this->answers($attempt->exam, true));
        $questionIds = array_keys($this->answers($attempt->exam, true));

        $shifted = array_combine($questionIds, [...array_slice($correct, 1), $correct[0]]);

        $this->actingAs($attempt->user)->post(route('worker.attempts.submit', $attempt), ['answers' => $shifted]);

        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_running_exam_page_does_not_reveal_correct_answers(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();

        $this->actingAs($attempt->user)
            ->get(route('worker.attempts.show', $attempt))
            ->assertOk()
            ->assertDontSee('is_correct')
            ->assertDontSee('✓');
    }

    public function test_only_the_registered_candidate_can_take_the_exam(): void
    {
        $attempt = ExamAttempt::factory()->create();

        $this->actingAs(User::factory()->activeCandidate()->create())
            ->post(route('worker.attempts.start', $attempt))
            ->assertForbidden();

        $this->assertNull($attempt->fresh()->started_at);
    }

    public function test_owning_lpk_can_write_an_evaluation_visible_to_the_candidate(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->withQuestions()->create();
        $attempt = ExamAttempt::factory()->for($exam)->failed()->create();

        $this->actingAs($lpk)->put(route('lpk.attempts.evaluate', $attempt), [
            'evaluation' => 'Perlu memperdalam materi K3 dan membaca gambar teknik.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($attempt->user)
            ->get(route('dashboard'))
            ->assertSee('Perlu memperdalam materi K3 dan membaca gambar teknik.');
    }

    public function test_company_and_other_lpk_can_not_write_evaluations(): void
    {
        $attempt = ExamAttempt::factory()->passed()->create();

        foreach ([User::factory()->lpk()->create(), User::factory()->company()->create()] as $user) {
            $this->actingAs($user)
                ->put(route('lpk.attempts.evaluate', $attempt), ['evaluation' => 'Evaluasi yang tidak sah.'])
                ->assertForbidden();
        }

        $this->assertNull($attempt->fresh()->evaluation);
    }
}
