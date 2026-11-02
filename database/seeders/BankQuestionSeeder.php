<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Base for seeders that fill one question bank category of every LPK.
 * Subclasses define CATEGORY and QUESTIONS; questions already in the bank
 * (same text) are skipped, so running a seeder twice is safe.
 */
abstract class BankQuestionSeeder extends Seeder
{
    public const CATEGORY = '';

    /**
     * Each item: [question, correct answer, [four wrong answers]].
     *
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    public const QUESTIONS = [];

    /**
     * Questions with their five options; the correct answer's position is
     * spread over A–E and stays the same for a given question.
     *
     * @return list<array{0: string, 1: list<string>, 2: int}>
     */
    public static function items(): array
    {
        return array_map(function (array $item) {
            [$question, $correct, $wrong] = $item;
            $correctIndex = crc32($question) % 5;
            $options = $wrong;
            array_splice($options, $correctIndex, 0, [$correct]);

            return [$question, $options, $correctIndex];
        }, static::QUESTIONS);
    }

    public function run(): void
    {
        Organization::query()->where('type', 'lpk')->each(function (Organization $lpk) {
            $category = $lpk->questionCategories()->firstOrCreate(
                ['name' => static::CATEGORY],
                ['description' => QuestionCategory::DEFAULTS[static::CATEGORY] ?? null],
            );

            $existing = $category->questions()->pluck('question')
                ->map(fn (string $text) => $this->normalize($text))
                ->flip();

            $added = 0;

            DB::transaction(function () use ($category, $existing, &$added) {
                foreach (static::items() as [$question, $options, $correctIndex]) {
                    if ($existing->has($this->normalize($question))) {
                        continue;
                    }

                    $category->questions()->create(['question' => $question])->syncOptions($options, $correctIndex);
                    $added++;
                }
            });

            $this->command?->info("{$lpk->name}: {$added} soal ".static::CATEGORY.' ditambahkan.');
        });
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->lower()->squish()->toString();
    }
}
