<?php

namespace App\Models;

use Database\Factories\BankQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['question'])]
class BankQuestion extends Model
{
    /** @use HasFactory<BankQuestionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<QuestionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    /**
     * @return HasMany<BankQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(BankQuestionOption::class)->orderBy('id');
    }

    /**
     * Replace the answer options. Blank texts are skipped; the option at
     * $correctIndex (an index into $texts) is marked as the right answer.
     *
     * @param  array<int, string|null>  $texts
     */
    public function syncOptions(array $texts, int $correctIndex): void
    {
        DB::transaction(function () use ($texts, $correctIndex) {
            $this->options()->delete();

            foreach ($texts as $index => $text) {
                if (filled($text)) {
                    $this->options()->create([
                        'text' => trim((string) $text),
                        'is_correct' => $index === $correctIndex,
                    ]);
                }
            }
        });
    }
}
