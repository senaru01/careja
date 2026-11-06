<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DocumentType;
use App\Enums\InterviewStatus;
use App\Enums\MedicalStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Number of minutes a two factor code stays valid.
     */
    public const TWO_FACTOR_CODE_LIFETIME = 10;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'approved_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Determine if login must go through the email OTP step: the global
     * switch must be on and the super admin must not have disabled it for this user.
     */
    public function requiresTwoFactor(): bool
    {
        return config('careja.two_factor_enabled') && $this->two_factor_enabled !== false;
    }

    /**
     * The super admin who approved this user.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The LPK or company this account belongs to. For a job seeker: the LPK
     * whose subdomain they registered on.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /**
     * @return HasMany<CandidateSkill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class)->orderByDesc('start_date');
    }

    /**
     * Total years of work experience (overlapping jobs counted once), with
     * one decimal. A job without an end date runs until today.
     */
    public function workExperienceYears(): float
    {
        $experiences = $this->relationLoaded('workExperiences') ? $this->workExperiences : $this->workExperiences()->get();

        $periods = $experiences
            ->filter(fn (WorkExperience $experience) => $experience->start_date !== null)
            ->map(fn (WorkExperience $experience) => [$experience->start_date, ($experience->end_date ?? now())->min(now())])
            ->sortBy(fn (array $period) => $period[0]->timestamp)
            ->values();

        $days = 0;
        $coveredUntil = null;

        foreach ($periods as [$start, $end]) {
            $start = $coveredUntil && $start->lessThan($coveredUntil) ? $coveredUntil : $start;
            if ($end->greaterThan($start)) {
                $days += $start->diffInDays($end);
                $coveredUntil = $end;
            }
        }

        return round($days / 365.25, 1);
    }

    /**
     * Experience as shown to people, e.g. "2,5 tahun" or "belum ada pengalaman".
     */
    public function workExperienceLabel(): string
    {
        $years = $this->workExperienceYears();

        return $years > 0 ? str_replace('.', ',', (string) $years).' tahun' : 'belum ada pengalaman';
    }

    /**
     * The fields the candidate worked in (job titles), most recent first.
     *
     * @return list<string>
     */
    public function workFields(): array
    {
        $experiences = $this->relationLoaded('workExperiences') ? $this->workExperiences : $this->workExperiences()->get();

        return $experiences->pluck('position')->filter()->map(fn (string $position) => trim($position))->unique()->values()->all();
    }

    /**
     * @return HasMany<CandidateDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class)->latest();
    }

    /**
     * @return HasMany<ExamToken, $this>
     */
    public function examTokens(): HasMany
    {
        return $this->hasMany(ExamToken::class)->latest('id');
    }

    public function availableExamTokens(): int
    {
        return $this->examTokens()->available()->count();
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->latest('scheduled_at');
    }

    /**
     * Scope a query to users still waiting for approval.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('approved_at');
    }

    /**
     * Scope a query to super admin users.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function superAdmins(Builder $query): void
    {
        $query->where('role', UserRole::SuperAdmin);
    }

    /**
     * Scope a query to job seekers with a verified, unexpired registration
     * payment, which makes them visible in the candidate list.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function activeCandidates(Builder $query): void
    {
        $query->where('role', UserRole::Worker)
            ->whereNotNull('approved_at')
            ->whereHas('payments', fn (Builder $payments) => $payments->active());
    }

    /**
     * Scope a query to job seekers whose data the viewer may see: an LPK sees
     * its own members, a company the members of its connected LPKs, the
     * super admin everybody.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function candidatesOf(Builder $query, User $viewer): void
    {
        $lpkIds = $viewer->visibleLpkIds();

        $query->where('role', UserRole::Worker)
            ->when($lpkIds !== null, fn (Builder $query) => $query->whereIn('organization_id', $lpkIds));
    }

    /**
     * The LPKs whose members this user may see, or null for all of them (super admin).
     *
     * @return list<int>|null
     */
    public function visibleLpkIds(): ?array
    {
        return match (true) {
            $this->isSuperAdmin() => null,
            $this->isLpk() => [(int) $this->organization_id],
            $this->isCompany() => $this->organization?->connectedLpks()->pluck('organizations.id')->map(fn ($id) => (int) $id)->all() ?? [],
            default => [],
        };
    }

    /**
     * Staff and members log in on their own organization's subdomain, the
     * super admin on the root domain.
     */
    public function canLogInOn(?Organization $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return $tenant === null;
        }

        return $tenant !== null && $this->organization_id === $tenant->id;
    }

    /**
     * Scope a query to candidates that passed at least one theory exam.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function passedTheory(Builder $query): void
    {
        $query->whereHas('examAttempts', fn (Builder $attempts) => $attempts->where('passed', true));
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isWorker(): bool
    {
        return $this->role === UserRole::Worker;
    }

    public function isLpk(): bool
    {
        return $this->role === UserRole::Lpk;
    }

    public function isCompany(): bool
    {
        return $this->role === UserRole::Company;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * The verified payment currently keeping the account active, if any.
     */
    public function activeMembership(): ?Payment
    {
        return $this->payments()->active()->reorder('expires_at', 'desc')->first();
    }

    public function hasActiveMembership(): bool
    {
        return $this->payments()->active()->exists();
    }

    /**
     * Determine if the candidate paid before but the membership period has ended.
     */
    public function hasExpiredMembership(): bool
    {
        return ! $this->hasActiveMembership()
            && $this->payments()->membership()->where('status', PaymentStatus::Verified)->exists();
    }

    /**
     * Determine if the given user may see this candidate's profile, photo and
     * documents. LPK staff see the active members of their LPK, company staff
     * the active members of their connected LPKs, plus candidates they
     * already tested or interviewed so they can finish that stage.
     */
    public function isCandidateVisibleTo(User $viewer): bool
    {
        if (! $this->isWorker()) {
            return false;
        }

        if ($viewer->is($this) || $viewer->isSuperAdmin()) {
            return true;
        }

        if (! $viewer->isLpk() && ! $viewer->isCompany()) {
            return false;
        }

        if ($this->hasActiveMembership() && in_array((int) $this->organization_id, $viewer->visibleLpkIds(), true)) {
            return true;
        }

        return $viewer->isLpk()
            ? $this->examAttempts()->whereRelation('exam', 'organization_id', $viewer->organization_id)->exists()
            : $this->interviews()->where('organization_id', $viewer->organization_id)->exists()
                || $this->examAttempts()->whereNotNull('registered_at')->whereRelation('exam', 'company_id', $viewer->organization_id)->exists();
    }

    /**
     * URL of the candidate's profile photo, or null when none was uploaded.
     */
    public function photoUrl(): ?string
    {
        $profile = $this->relationLoaded('candidateProfile') ? $this->candidateProfile : $this->candidateProfile()->first();

        return $profile?->photo_path
            ? route('candidates.photo', ['candidate' => $this, 'v' => $profile->updated_at?->timestamp])
            : null;
    }

    /**
     * Determine if a membership payment is waiting (for the QRIS scan or the admin).
     */
    public function hasPendingPayment(): bool
    {
        return $this->payments()->membership()->where('status', PaymentStatus::Pending)->exists();
    }

    public function hasPassedTheory(): bool
    {
        return $this->examAttempts()->where('passed', true)->exists();
    }

    /**
     * Determine if the candidate filled every required profile field and uploaded a CV.
     */
    public function hasCompleteCandidateProfile(): bool
    {
        return (bool) $this->candidateProfile?->isComplete()
            && $this->documents()->where('type', DocumentType::Cv)->exists();
    }

    /**
     * Short description of where the candidate currently is in the recruitment flow.
     */
    public function candidateStage(): string
    {
        $interview = $this->interviews()->first();

        return match (true) {
            $interview?->medical_status === MedicalStatus::Fit => 'Diterima Perusahaan',
            $interview?->medical_status === MedicalStatus::Unfit => 'Tidak Lolos Medical',
            $interview?->status === InterviewStatus::Passed => 'Tahap Medical Checkup',
            $interview?->status === InterviewStatus::Failed => 'Tidak Lolos Wawancara',
            $interview?->status === InterviewStatus::Scheduled => 'Tahap Wawancara',
            $this->hasExpiredMembership() => 'Masa Aktif Habis',
            $this->hasPassedTheory() => 'Lulus Tes Teori',
            $this->examAttempts()->whereNotNull('registered_at')->whereNull('withdrawn_at')->exists() => 'Tahap Tes Teori',
            $this->hasActiveMembership() => 'Kandidat Aktif',
            $this->hasPendingPayment() => 'Verifikasi Pembayaran',
            $this->hasCompleteCandidateProfile() => 'Menunggu Pembayaran',
            default => 'Lengkapi Profil',
        };
    }

    /**
     * Grant the user access to the application.
     */
    public function approve(User $approver): void
    {
        $this->forceFill([
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ])->save();
    }

    /**
     * Revoke the user's access to the application.
     */
    public function revokeApproval(): void
    {
        $this->forceFill([
            'approved_at' => null,
            'approved_by' => null,
        ])->save();
    }

    /**
     * Generate, store (hashed) and return a fresh six digit two factor code.
     */
    public function generateTwoFactorCode(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::TWO_FACTOR_CODE_LIFETIME),
        ])->save();

        return $code;
    }

    /**
     * Determine if the given code matches the stored, unexpired two factor code.
     */
    public function hasValidTwoFactorCode(string $code): bool
    {
        return $this->two_factor_code !== null
            && $this->two_factor_expires_at?->isFuture()
            && Hash::check($code, $this->two_factor_code);
    }

    /**
     * Clear the stored two factor code.
     */
    public function resetTwoFactorCode(): void
    {
        $this->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();
    }
}
