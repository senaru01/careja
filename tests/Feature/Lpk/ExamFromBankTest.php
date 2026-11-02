<?php

namespace Tests\Feature\Lpk;

use App\Models\BankQuestion;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamFromBankTest extends TestCase
{
    use RefreshDatabase;

    private User $lpk;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lpk = User::factory()->lpk()->create();
        $this->exam = Exam::factory()->for($this->lpk->organization)->draft()->create();
    }

    private function fillCategory(string $name, int $count): QuestionCategory
    {
        $category = $this->lpk->organization->questionCategories()->where('name', $name)->firstOrFail();
        BankQuestion::factory()->count($count)->for($category, 'category')->withOptions()->create();

        return $category;
    }

    public function test_lpk_picks_a_random_subset_from_a_category(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 100);

        $this->actingAs($this->lpk)
            ->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 50])
            ->assertSessionHasNoErrors();

        $questions = $this->exam->questions()->with('options')->get();
        $this->assertCount(50, $questions);
        $this->assertCount(50, $questions->pluck('bank_question_id')->unique());
        $this->assertTrue($questions->every(fn ($question) => $question->question_category_id === $psikotes->id));
        $this->assertTrue($questions->every(fn ($question) => $question->options->where('is_correct', true)->count() === 1));
    }

    public function test_categories_can_be_combined_without_picking_a_question_twice(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 40);
        $umum = $this->fillCategory('Pengetahuan Umum', 30);

        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 30]);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $umum->id, 'count' => 20]);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 10]);

        $this->assertSame(40, $this->exam->questions()->where('question_category_id', $psikotes->id)->distinct()->count('bank_question_id'));
        $this->assertSame(20, $this->exam->questions()->where('question_category_id', $umum->id)->count());
    }

    public function test_can_not_pick_more_questions_than_available(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 10);

        $this->actingAs($this->lpk)
            ->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 11])
            ->assertSessionHasErrorsIn('bank', 'count');

        $this->assertSame(0, $this->exam->questions()->count());
    }

    public function test_editing_the_bank_does_not_change_exam_questions(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 1);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 1]);
        $original = $this->exam->questions()->firstOrFail()->question;

        $psikotes->questions()->firstOrFail()->update(['question' => 'Diubah setelah diambil?']);

        $this->assertSame($original, $this->exam->questions()->firstOrFail()->question);
    }

    public function test_can_not_use_another_lpks_category(): void
    {
        $otherCategory = QuestionCategory::factory()->withQuestions(5)->create();

        $this->actingAs($this->lpk)
            ->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $otherCategory->id, 'count' => 5])
            ->assertSessionHasErrorsIn('bank', 'question_category_id');

        $this->assertSame(0, $this->exam->questions()->count());
    }

    public function test_locked_exam_can_not_take_more_questions_or_be_cleared(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 10);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 5]);
        ExamAttempt::factory()->for($this->exam)->create();

        $this->actingAs($this->lpk)
            ->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 5])
            ->assertForbidden();
        $this->actingAs($this->lpk)->delete(route('lpk.exams.questions.clear', $this->exam))->assertForbidden();

        $this->assertSame(5, $this->exam->questions()->count());
    }

    public function test_unassigned_exam_can_be_cleared(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 10);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 10]);

        $this->actingAs($this->lpk)->delete(route('lpk.exams.questions.clear', $this->exam));

        $this->assertSame(0, $this->exam->questions()->count());
    }

    public function test_result_shows_score_per_category(): void
    {
        $psikotes = $this->fillCategory('Psikotes', 2);
        $umum = $this->fillCategory('Pengetahuan Umum', 2);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $psikotes->id, 'count' => 2]);
        $this->actingAs($this->lpk)->post(route('lpk.exams.bank.store', $this->exam), ['question_category_id' => $umum->id, 'count' => 2]);

        $attempt = ExamAttempt::factory()->for($this->exam)->started()->create();
        $answers = $this->exam->questions()->with('options')->get()->mapWithKeys(fn ($question) => [
            $question->id => $question->options->firstWhere('is_correct', $question->question_category_id === $psikotes->id)->id,
        ])->all();

        $this->actingAs($attempt->user)->post(route('worker.attempts.submit', $attempt), ['answers' => $answers]);

        $breakdown = $attempt->fresh()->categoryBreakdown();
        $this->assertSame(['correct' => 2, 'total' => 2], $breakdown['Psikotes']);
        $this->assertSame(['correct' => 0, 'total' => 2], $breakdown['Pengetahuan Umum']);

        $this->actingAs($attempt->user)->get(route('worker.attempts.show', $attempt))->assertSee('Pengetahuan Umum');
    }

    public function test_exam_page_shows_bank_categories(): void
    {
        $this->fillCategory('Psikotes', 3);

        $this->actingAs($this->lpk)
            ->get(route('lpk.exams.show', $this->exam))
            ->assertOk()
            ->assertSee('Psikotes (3 soal tersedia)');
    }
}
