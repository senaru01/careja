<?php

namespace App\Models;

use Database\Factories\BankQuestionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['text', 'is_correct'])]
class BankQuestionOption extends Model
{
    /** @use HasFactory<BankQuestionOptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BankQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(BankQuestion::class, 'bank_question_id');
    }
}
