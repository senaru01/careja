<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every column is written by the application itself (assignment, grading,
 * evaluation) through forceFill, so nothing is mass assignable.
 */
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * Extra seconds accepted after the timer ends, to absorb network latency.
     */
    public const GRACE_SECONDS = 60;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qualification_confirmed_at' => 'datetime',
            'registered_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'passed' => 'boolean',
            'evaluated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The position the participant applied for.
     *
     * @return BelongsTo<ExamPosition, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(ExamPosition::class, 'exam_position_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * @return HasMany<ExamAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Determine if the seat is paid (or covered by a token) and still taken.
     */
    public function isRegistered(): bool
    {
        return $this->registered_at !== null && $this->withdrawn_at === null;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    /**
     * Determine if the participant may still leave the session for a token:
     * only when the schedule was postponed after they registered, and only
     * before the (new) start.
     */
    public function canWithdraw(): bool
    {
        return $this->isRegistered()
            && ! $this->isStarted()
            && $this->exam->isOpenForRegistration()
            && $this->exam->wasPostponedAfter($this->registered_at);
    }

    /**
     * Determine if the participant can open the exam right now.
     */
    public function canStart(): bool
    {
        return $this->isRegistered() && ! $this->isStarted() && $this->exam->isRunning();
    }

    /**
     * Registered but never started before the session closed.
     */
    public function isAbsent(): bool
    {
        return $this->isRegistered() && ! $this->isStarted() && $this->exam->hasEnded();
    }

    /**
     * Seconds between start and submission, used as ranking tie-breaker.
     */
    public function durationSeconds(): int
    {
        return $this->started_at && $this->submitted_at
            ? (int) $this->started_at->diffInSeconds($this->submitted_at)
            : PHP_INT_MAX;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null;
    }

    /**
     * The timer ends after the exam duration, but never after the session closes.
     */
    public function deadline(): ?CarbonInterface
    {
        $deadline = $this->started_at?->copy()->addMinutes($this->exam->duration_minutes);

        return $deadline && $this->exam->ends_at ? $deadline->min($this->exam->ends_at) : $deadline;
    }

    public function isPastDeadline(): bool
    {
        return $this->isStarted()
            && now()->greaterThan($this->deadline()->copy()->addSeconds(self::GRACE_SECONDS));
    }

    public function start(): void
    {
        if ($this->isStarted()) {
            return;
        }

        $this->forceFill(['started_at' => now()])->save();
    }

    /**
     * Determine if answers may still be saved: started, not submitted and
     * within the deadline (plus the grace period).
     */
    public function acceptsAnswers(): bool
    {
        return $this->isStarted() && ! $this->isSubmitted() && ! $this->isPastDeadline();
    }

    /**
     * Save one answer while the test runs, so progress survives a refresh
     * and the live score board can follow it.
     */
    public function saveAnswer(ExamQuestion $question, ?ExamOption $option): void
    {
        $this->answers()->updateOrCreate(
            ['exam_question_id' => $question->id],
            ['exam_option_id' => $option?->id, 'is_correct' => (bool) $option?->is_correct],
        );
    }

    /**
     * Grade the answers and lock the attempt. Answers saved during the test
     * count, the submitted ones replace them. Answers sent after the deadline
     * are ignored, so a late submission scores only what was saved on time.
     *
     * @param  array<int|string, int|string|null>  $selectedOptions  question id => option id
     */
    public function submit(array $selectedOptions): void
    {
        if ($this->isSubmitted()) {
            return;
        }

        if ($this->isPastDeadline()) {
            $selectedOptions = [];
        }

        DB::transaction(function () use ($selectedOptions) {
            if (static::query()->lockForUpdate()->find($this->id)?->isSubmitted()) {
                return;
            }

            $selectedOptions = array_replace(
                $this->answers()->pluck('exam_option_id', 'exam_question_id')->all(),
                array_filter($selectedOptions, fn ($optionId) => filled($optionId)),
            );

            $questions = $this->exam->questions()->with('options')->get();
            $correctCount = 0;

            foreach ($questions as $question) {
                $option = $question->options->firstWhere('id', (int) ($selectedOptions[$question->id] ?? 0));
                $correctCount += (int) (bool) $option?->is_correct;

                $this->saveAnswer($question, $option);
            }

            $total = $questions->count();
            $score = $total > 0 ? (int) round($correctCount / $total * 100) : 0;

            $this->forceFill([
                'submitted_at' => now(),
                'total_questions' => $total,
                'correct_count' => $correctCount,
                'score' => $score,
                'passed' => $score >= $this->exam->passing_score,
            ])->save();
        });
    }

    /**
     * Close an attempt whose timer ran out without a submission.
     */
    public function finalizeIfExpired(): void
    {
        if (! $this->isSubmitted() && $this->isPastDeadline()) {
            $this->submit([]);
        }
    }

    public function evaluate(User $lpkStaff, string $evaluation): void
    {
        $this->forceFill([
            'evaluation' => $evaluation,
            'evaluated_by' => $lpkStaff->id,
            'evaluated_at' => now(),
        ])->save();
    }

    /**
     * Correct answers per question category, e.g. ['Psikotes' => ['correct' => 25, 'total' => 30]].
     *
     * @return Collection<string, array{correct: int, total: int}>
     */
    public function categoryBreakdown(): Collection
    {
        return $this->loadMissing('answers.question.category')->answers
            ->groupBy(fn (ExamAnswer $answer) => $answer->question?->category?->name ?? 'Soal Manual')
            ->map(fn (Collection $answers) => [
                'correct' => $answers->where('is_correct', true)->count(),
                'total' => $answers->count(),
            ]);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->passed === true => 'Lulus',
            $this->passed === false => 'Tidak Lulus',
            $this->isWithdrawn() => 'Keluar Sesi',
            $this->registered_at === null => 'Menunggu Pembayaran',
            $this->isStarted() => 'Sedang Dikerjakan',
            $this->isAbsent() => 'Tidak Hadir',
            $this->exam->isWaitingForStart() => 'Menunggu Dimulai',
            default => 'Siap Tes',
        };
    }
}
