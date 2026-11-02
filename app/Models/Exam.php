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

/**
 * A test session: an online theory exam run by an LPK for one company, with
 * the position, qualifications, quota and schedule announced up front.
 */
#[Fillable([
    'company_id', 'title', 'position', 'qualifications', 'quota', 'min_participants',
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
            $this->ends_at === null || now()->lessThan($this->ends_at) => ExamPhase::Running,
            default => ExamPhase::Finished,
        };
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
     * Determine if a participant registered before the schedule was last
     * postponed, which is the only case where leaving the session is allowed.
     */
    public function wasPostponedAfter(?\DateTimeInterface $moment): bool
    {
        return $moment !== null && $this->reschedules()->where('created_at', '>', $moment)->exists();
    }

    /**
     * Participants that passed, best first: highest score, then the fastest
     * finish, then whoever submitted first. Each attempt gets a `rank`.
     *
     * @return Collection<int, ExamAttempt>
     */
    public function ranking(): Collection
    {
        return $this->participants()
            ->where('passed', true)
            ->with(['user.candidateProfile', 'user.skills'])
            ->get()
            ->sort(function (ExamAttempt $a, ExamAttempt $b) {
                return [$b->score, $a->durationSeconds(), $a->submitted_at, $a->id]
                    <=> [$a->score, $b->durationSeconds(), $b->submitted_at, $b->id];
            })
            ->values()
            ->each(fn (ExamAttempt $attempt, int $index) => $attempt->rank = $index + 1);
    }

    /**
     * Interviews that still hold one of the quota seats: scheduled, or passed
     * and not declared unfit at the medical checkup.
     */
    public function filledSeats(): int
    {
        return $this->interviews()
            ->where(fn ($query) => $query
                ->where('status', InterviewStatus::Scheduled)
                ->orWhere(fn ($query) => $query
                    ->where('status', InterviewStatus::Passed)
                    ->where(fn ($query) => $query->whereNull('medical_status')->orWhere('medical_status', '!=', MedicalStatus::Unfit->value))))
            ->count();
    }

    public function openSeats(): int
    {
        return max(0, (int) $this->quota - $this->filledSeats());
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
