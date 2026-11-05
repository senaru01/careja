<?php

namespace App\Models;

use App\Models\Concerns\DescribesPositionRequirements;
use Database\Factories\ExamPositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A position offered in a test session, with its own quota. Participants
 * apply for one position and are ranked against the others who chose it.
 */
class ExamPosition extends Model
{
    /** @use HasFactory<ExamPositionFactory> */
    use DescribesPositionRequirements, HasFactory;

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
