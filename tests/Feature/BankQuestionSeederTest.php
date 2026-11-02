<?php

namespace Tests\Feature;

use App\Models\Organization;
use Database\Seeders\BankQuestionSeeder;
use Database\Seeders\PengetahuanUmumQuestionSeeder;
use Database\Seeders\PsikotesQuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BankQuestionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<BankQuestionSeeder>}>
     */
    public static function seeders(): array
    {
        return [
            'psikotes' => [PsikotesQuestionSeeder::class],
            'pengetahuan umum' => [PengetahuanUmumQuestionSeeder::class],
        ];
    }

    /**
     * @param  class-string<BankQuestionSeeder>  $seeder
     */
    #[DataProvider('seeders')]
    public function test_question_data_is_well_formed(string $seeder): void
    {
        $questions = $seeder::QUESTIONS;

        $this->assertGreaterThanOrEqual(100, count($questions));
        $this->assertCount(count($questions), array_unique(array_column($questions, 0)), 'Duplicate question text.');

        foreach ($questions as [$question, $correct, $wrong]) {
            $this->assertCount(4, $wrong, "Need 4 wrong options: {$question}");
            $this->assertNotContains($correct, $wrong, "Correct answer listed as wrong: {$question}");
            $this->assertCount(4, array_unique($wrong), "Duplicate wrong options: {$question}");
        }
    }

    /**
     * Recompute the number series answers instead of trusting the typed key.
     */
    public function test_number_series_answers_are_correct(): void
    {
        $expected = [
            '2, 4, 6, 8, ...' => 10, '3, 6, 12, 24, ...' => 48, '1, 4, 9, 16, 25, ...' => 36,
            '5, 10, 8, 16, 14, ...' => 28, '1, 1, 2, 3, 5, 8, ...' => 13, '100, 95, 85, 70, ...' => 50,
            '1, 8, 27, 64, ...' => 5 ** 3, '4, 7, 12, 19, 28, ...' => 28 + 11, '2, 6, 18, 54, ...' => 54 * 3,
            '10, 13, 11, 14, 12, ...' => 12 + 3, '1, 2, 6, 24, 120, ...' => 120 * 6, '3, 5, 9, 17, 33, ...' => 33 * 2 - 1,
            '1, 3, 7, 15, 31, ...' => 31 * 2 + 1, '2, 5, 11, 23, ...' => 23 * 2 + 1, '20, 18, 15, 11, 6, ...' => 6 - 6,
            '1, 2, 4, 7, 11, 16, ...' => 16 + 6, '5, 7, 10, 14, 19, ...' => 19 + 6, '0, 3, 8, 15, 24, ...' => 6 ** 2 - 1,
        ];

        $answers = collect(PsikotesQuestionSeeder::QUESTIONS)->mapWithKeys(fn ($item) => [$item[0] => $item[1]]);

        foreach ($expected as $series => $value) {
            $this->assertSame((string) $value, $answers['Lanjutkan deret: '.$series], "Wrong key for {$series}");
        }
    }

    public function test_seeder_fills_the_psikotes_category_once(): void
    {
        $lpk = Organization::factory()->lpk()->create();

        $this->seed(PsikotesQuestionSeeder::class);
        $this->seed(PsikotesQuestionSeeder::class);

        $category = $lpk->questionCategories()->where('name', 'Psikotes')->firstOrFail();
        $this->assertSame(count(PsikotesQuestionSeeder::QUESTIONS), $category->questions()->count());

        $question = $category->questions()->where('question', 'Lanjutkan deret: 2, 6, 18, 54, ...')->with('options')->firstOrFail();
        $this->assertCount(5, $question->options);
        $this->assertSame('162', $question->options->firstWhere('is_correct', true)->text);
    }

    public function test_both_seeders_fill_their_own_category(): void
    {
        $lpk = Organization::factory()->lpk()->create();

        $this->seed([PsikotesQuestionSeeder::class, PengetahuanUmumQuestionSeeder::class]);

        $counts = $lpk->questionCategories()->withCount('questions')->pluck('questions_count', 'name');
        $this->assertSame(count(PsikotesQuestionSeeder::QUESTIONS), $counts['Psikotes']);
        $this->assertSame(count(PengetahuanUmumQuestionSeeder::QUESTIONS), $counts['Pengetahuan Umum']);
    }
}
