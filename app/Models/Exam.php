<?php

namespace App\Models;

use App\Enums\ExamPhase;
use App\Enums\InterviewStatus;
use App\Enums\MedicalStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A test session: an online theory exam run by an LPK for one company, with
 * the position, qualifications, quota and schedule announced up front.
 */
#[Fillable([
    'company_id', 'recruitment_request_id', 'title', 'position', 'qualifications', 'quota', 'min_participants',
    'description', 'duration_minutes', 'passing_score', 'starts_at', 'ends_at',
])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'min_participants' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'started_at' => 'datetime',
        ];
    }

    /**
     * The LPK running the session.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The company the session recruits for.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'company_id');
    }

    /**
     * The company request this session follows up, if any.
     *
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    /**
     * Positions offered in this session, each with its own quota.
     *
     * @return HasMany<ExamPosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(ExamPosition::class)->orderBy('id');
    }

    /**
     * Replace the position table. The session's position and quota columns
     * keep the summary (names and total) used in lists and notifications.
     *
     * @param  array<int, array<string, mixed>>  $positions
     */
    public function syncPositions(array $positions): void
    {
        DB::transaction(function () use ($positions) {
            $this->positions()->delete();
            $this->positions()->createMany($positions);

            $this->forceFill([
                'position' => collect($positions)->pluck('position')->implode(', '),
                'quota' => collect($positions)->sum('quota'),
            ])->save();
        });

        $this->unsetRelation('positions');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ExamQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('id');
    }

    /**
     * Every registration, including unpaid and withdrawn ones.
     *
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Participants that paid (or used a token) and did not leave: "siap tes".
     *
     * @return HasMany<ExamAttempt, $this>
     */
    public function participants(): HasMany
    {
        return $this->attempts()->whereNotNull('registered_at')->whereNull('withdrawn_at');
    }

    /**
     * @return HasMany<ExamReschedule, $this>
     */
    public function reschedules(): HasMany
    {
        return $this->hasMany(ExamReschedule::class)->latest('id');
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function phase(): ExamPhase
    {
        return match (true) {
            ! $this->isPublished() => ExamPhase::Draft,
            $this->starts_at === null || now()->lessThan($this->starts_at) => ExamPhase::Open,
            $this->ends_at !== null && now()->greaterThanOrEqualTo($this->ends_at) => ExamPhase::Finished,
            $this->started_at === null => ExamPhase::Waiting,
            default => ExamPhase::Running,
        };
    }

    /**
     * Registration is closed and the participants wait in the room for the
     * LPK to start the test.
     */
    public function isWaitingForStart(): bool
    {
        return $this->phase() === ExamPhase::Waiting;
    }

    /**
     * Start the test for everyone in the room. The session closes when the
     * duration is over, so every participant works within the same window.
     */
    public function startNow(): void
    {
        $this->forceFill([
            'started_at' => now(),
            'ends_at' => now()->addMinutes($this->duration_minutes),
        ])->save();
    }

    public function isOpenForRegistration(): bool
    {
        return $this->phase() === ExamPhase::Open;
    }

    public function isRunning(): bool
    {
        return $this->phase() === ExamPhase::Running;
    }

    public function hasEnded(): bool
    {
        return $this->phase() === ExamPhase::Finished;
    }

    /**
     * Once published, everything announced (questions, qualifications, quota,
     * passing score) is frozen so every participant is judged by the same rules.
     * Only the schedule can still be postponed.
     */
    public function isLocked(): bool
    {
        return $this->isPublished() || $this->participants()->exists();
    }

    /**
     * The open QRIS payment for publishing this session, if the LPK started one.
     */
    public function pendingPublicationPayment(): ?Payment
    {
        return $this->payments()
            ->where('purpose', PaymentPurpose::ExamSession)
            ->where('status', PaymentStatus::Pending)
            ->latest('id')
            ->first();
    }

    /**
     * The most recent transfer proof the admin rejected for this session and
     * purpose, so the payer sees why and can send a new one.
     */
    public function latestRejectedPayment(PaymentPurpose $purpose, ?User $payer = null): ?Payment
    {
        return $this->payments()
            ->where('purpose', $purpose)
            ->where('status', PaymentStatus::Rejected)
            ->when($payer, fn ($query) => $query->where('user_id', $payer->id))
            ->latest('id')
            ->first();
    }

    /**
     * Determine if the company paid the interview fee that unlocks inviting
     * candidates from this session's ranking.
     */
    public function hasPaidInterviewAccess(): bool
    {
        return $this->payments()->where('purpose', PaymentPurpose::InterviewAccess)->paid()->exists();
    }

    /**
     * The open QRIS payment for the interview fee, if the company started one.
     */
    public function pendingInterviewAccessPayment(): ?Payment
    {
        return $this->payments()
            ->where('purpose', PaymentPurpose::InterviewAccess)
            ->pending()
            ->latest('id')
            ->first();
    }

    /**
     * Determine if a participant registered before the schedule was last
     * postponed, which is the only case where leaving the session is allowed.
     */
    public function wasPostponedAfter(?\DateTimeInterface $moment): bool
    {
        return $moment !== null && $this->reschedules()->where('created_at', '>', $moment)->exists();
    }

    /**
     * Participants that passed, best first: highest score, then the fastest
     * finish, then whoever submitted first. Each attempt gets a `rank`. With a
     * position, only the participants that applied for it are ranked.
     *
     * @return Collection<int, ExamAttempt>
     */
    public function ranking(?ExamPosition $position = null): Collection
    {
        return $this->participants()
            ->where('passed', true)
            ->when($position, fn ($query) => $query->where('exam_position_id', $position->id))
            ->with(['user.candidateProfile', 'user.skills', 'position'])
            ->get()
            ->sort(function (ExamAttempt $a, ExamAttempt $b) {
                return [$b->score, $a->durationSeconds(), $a->submitted_at, $a->id]
                    <=> [$a->score, $b->durationSeconds(), $b->submitted_at, $b->id];
            })
            ->values()
            ->each(fn (ExamAttempt $attempt, int $index) => $attempt->rank = $index + 1);
    }

    /**
     * One ranking per position (or a single one for sessions without a
     * position table), each with its own quota.
     *
     * @return Collection<int, array{position: ExamPosition|null, label: string, quota: int, ranking: Collection<int, ExamAttempt>}>
     */
    public function rankingGroups(): Collection
    {
        $positions = $this->relationLoaded('positions') ? $this->positions : $this->positions()->get();

        if ($positions->isEmpty()) {
            return collect([['position' => null, 'label' => (string) ($this->position ?? $this->title), 'quota' => (int) $this->quota, 'ranking' => $this->ranking()]]);
        }

        return $positions->map(fn (ExamPosition $position) => [
            'position' => $position,
            'label' => $position->position,
            'quota' => $position->quota,
            'ranking' => $this->ranking($position),
        ]);
    }

    /**
     * Interviews that still hold one of the quota seats (of one position, if
     * given): scheduled, or passed and not declared unfit at the medical checkup.
     */
    public function filledSeats(?ExamPosition $position = null): int
    {
        return $this->interviews()
            ->when($position, fn ($query) => $query->where('exam_position_id', $position->id))
            ->where(fn ($query) => $query
                ->where('status', InterviewStatus::Scheduled)
                ->orWhere(fn ($query) => $query
                    ->where('status', InterviewStatus::Passed)
                    ->where(fn ($query) => $query->whereNull('medical_status')->orWhere('medical_status', '!=', MedicalStatus::Unfit->value))))
            ->count();
    }

    public function openSeats(?ExamPosition $position = null): int
    {
        return max(0, (int) ($position ? $position->quota : $this->quota) - $this->filledSeats($position));
    }

    /**
     * Candidates that passed the interview and the medical checkup.
     */
    public function hiredCount(): int
    {
        return $this->interviews()
            ->where('status', InterviewStatus::Passed)
            ->where('medical_status', MedicalStatus::Fit)
            ->count();
    }
}
