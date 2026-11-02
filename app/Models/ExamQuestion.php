<?php

namespace App\Models;

use Database\Factories\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['question', 'question_category_id', 'bank_question_id'])]
class ExamQuestion extends Model
{
    /** @use HasFactory<ExamQuestionFactory> */
    use HasFactory;

    /**
     * Bank category the question was copied from (null for questions typed into the exam).
     *
     * @return BelongsTo<QuestionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return HasMany<ExamOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ExamOption::class)->orderBy('id');
    }
}
