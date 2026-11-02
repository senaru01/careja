<?php

namespace App\Models;

use Database\Factories\ExamTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A free seat in a future test session. Issued when a job seeker leaves a
 * session after it was postponed (or paid for a session that already ended).
 * Written by the application only (forceFill).
 */
class ExamToken extends Model
{
    /** @use HasFactory<ExamTokenFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ExamToken>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->whereNull('used_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function sourceAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'source_attempt_id');
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function usedAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'used_attempt_id');
    }
}
