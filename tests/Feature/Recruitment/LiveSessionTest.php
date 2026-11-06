<?php

namespace Tests\Feature\Recruitment;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    private function answer(ExamAttempt $attempt, ExamQuestion $question, bool $correct): void
    {
        $this->actingAs($attempt->user)->postJson(route('worker.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'option_id' => $question->options->firstWhere('is_correct', $correct)->id,
        ])->assertOk();
    }

    public function test_participants_wait_until_the_lpk_starts_the_test(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->waitingForStart()->withQuestions()->create(['duration_minutes' => 45]);
        $attempt = ExamAttempt::factory()->for($exam)->create();

        $this->actingAs($attempt->user)->post(route('worker.attempts.start', $attempt))->assertSessionHasErrors('attempt');
        $this->assertNull($attempt->fresh()->started_at);

        $this->actingAs($lpk)->get(route('lpk.exams.show', $exam))->assertOk()->assertSee('Mulai Tes Sekarang');
        $this->actingAs($lpk)->get(route('dashboard'))->assertOk()->assertSee(route('sessions.live', $exam));

        $this->actingAs($lpk)->post(route('lpk.exams.start', $exam))->assertRedirect(route('sessions.live', $exam));

        $exam->refresh();
        $this->assertTrue($exam->isRunning());
        $this->assertEqualsWithDelta(now()->addMinutes(45)->timestamp, $exam->ends_at->timestamp, 5);

        $this->actingAs($attempt->user)->post(route('worker.attempts.start', $attempt));
        $this->assertNotNull($attempt->fresh()->started_at);
    }

    public function test_test_can_not_be_started_before_the_schedule_or_twice(): void
    {
        $lpk = User::factory()->lpk()->create();
        $upcoming = Exam::factory()->for($lpk->organization)->upcoming()->create();
        $running = Exam::factory()->for($lpk->organization)->create();
        $otherLpksExam = Exam::factory()->waitingForStart()->create();

        $this->actingAs($lpk)->post(route('lpk.exams.start', $upcoming))->assertForbidden();
        $this->actingAs($lpk)->post(route('lpk.exams.start', $running))->assertForbidden();
        $this->actingAs($lpk)->post(route('lpk.exams.start', $otherLpksExam))->assertForbidden();
        $this->assertNull($upcoming->fresh()->started_at);
    }

    public function test_answers_are_saved_while_working_and_shown_on_the_live_board(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $questions = $attempt->exam->questions()->with('options')->get();
        $lpk = User::factory()->lpk($attempt->exam->organization)->create();

        $this->answer($attempt, $questions[0], true);
        $this->answer($attempt, $questions[1], false);
        $this->answer($attempt, $questions[1], true);

        $this->actingAs($attempt->user)->get(route('worker.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('value="'.$questions[0]->options->firstWhere('is_correct', true)->id.'" class="text-blue-700"', false);

        $row = $this->actingAs($lpk)->getJson(route('sessions.live.data', $attempt->exam))
            ->assertOk()
            ->json('participants.0');

        $this->assertSame('working', $row['status']);
        $this->assertSame(2, $row['answered']);
        $this->assertSame(2, $row['correct']);
        $this->assertSame(67, $row['score']);
    }

    public function test_saved_answers_count_when_the_attempt_is_submitted_or_expires(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $questions = $attempt->exam->questions()->with('options')->get();

        $this->answer($attempt, $questions[0], true);
        $this->answer($attempt, $questions[1], true);

        // Submitting only the last answer keeps the two saved ones.
        $this->actingAs($attempt->user)->post(route('worker.attempts.submit', $attempt), [
            'answers' => [$questions[2]->id => $questions[2]->options->firstWhere('is_correct', true)->id],
        ]);

        $this->assertSame(100, $attempt->fresh()->score);
        $this->assertSame(3, $attempt->answers()->count());

        $expired = ExamAttempt::factory()->started()->create();
        $expiredQuestion = $expired->exam->questions()->with('options')->first();
        $this->answer($expired, $expiredQuestion, true);
        $this->travel($expired->exam->duration_minutes + 5)->minutes();

        $this->actingAs($expired->user)->postJson(route('worker.attempts.answer', $expired), [
            'question_id' => $expiredQuestion->id,
            'option_id' => $expiredQuestion->options->firstWhere('is_correct', false)->id,
        ])->assertConflict();

        $expired->fresh()->finalizeIfExpired();
        $this->assertSame(1, $expired->fresh()->correct_count);
    }

    public function test_answer_must_belong_to_the_attempts_exam(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $foreign = ExamQuestion::factory()->create();

        $this->actingAs($attempt->user)->postJson(route('worker.attempts.answer', $attempt), [
            'question_id' => $foreign->id,
            'option_id' => 1,
        ])->assertNotFound();

        $other = ExamAttempt::factory()->started()->create();
        $question = $attempt->exam->questions()->with('options')->first();
        $this->actingAs($other->user)->postJson(route('worker.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'option_id' => $question->options->first()->id,
        ])->assertForbidden();
    }

    public function test_live_board_is_for_the_lpk_and_the_company_of_the_session(): void
    {
        $exam = Exam::factory()->withQuestions()->create();
        ExamAttempt::factory()->for($exam)->create();

        $this->actingAs(User::factory()->lpk($exam->organization)->create())->get(route('sessions.live', $exam))->assertOk();
        $this->actingAs(User::factory()->company($exam->company)->create())->getJson(route('sessions.live.data', $exam))->assertOk();
        $this->actingAs(User::factory()->company()->create())->getJson(route('sessions.live.data', $exam))->assertForbidden();
        $this->actingAs(User::factory()->lpk()->create())->getJson(route('sessions.live.data', $exam))->assertForbidden();
        $this->actingAs(User::factory()->memberOf($exam->organization)->activeCandidate()->create())->getJson(route('sessions.live.data', $exam))->assertForbidden();
    }
}
