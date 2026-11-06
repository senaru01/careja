<?php

namespace App\Models;

use App\Enums\OrganizationType;
use App\Enums\PaymentCollector;
use App\Enums\PaymentPurpose;
use App\Enums\UserRole;
use App\Services\Tenancy;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An LPK or a company. Each works on its own subdomain (oki.careja.id). An
 * LPK has its own members, fees and payment settings; a company is
 * connected to the LPKs it recruits from.
 */
#[Fillable([
    'type', 'name', 'subdomain', 'email', 'phone', 'address', 'description',
    'membership_fee', 'exam_session_fee', 'exam_registration_fee', 'interview_fee', 'medical_checkup_fee',
    'membership_collected_by', 'careja_share_percent', 'bank_name', 'bank_account_number', 'bank_account_holder',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * Every new LPK starts with the default question bank categories.
     */
    protected static function booted(): void
    {
        static::created(function (Organization $organization) {
            if ($organization->type !== OrganizationType::Lpk) {
                return;
            }

            foreach (QuestionCategory::DEFAULTS as $name => $description) {
                $organization->questionCategories()->create(['name' => $name, 'description' => $description]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'membership_fee' => 'integer',
            'exam_session_fee' => 'integer',
            'exam_registration_fee' => 'integer',
            'interview_fee' => 'integer',
            'medical_checkup_fee' => 'integer',
            'membership_collected_by' => PaymentCollector::class,
            'careja_share_percent' => 'float',
        ];
    }

    /**
     * @param  Builder<Organization>  $query
     */
    #[Scope]
    protected function lpks(Builder $query): void
    {
        $query->where('type', OrganizationType::Lpk);
    }

    /**
     * @param  Builder<Organization>  $query
     */
    #[Scope]
    protected function companies(Builder $query): void
    {
        $query->where('type', OrganizationType::Company);
    }

    public function isLpk(): bool
    {
        return $this->type === OrganizationType::Lpk;
    }

    public function isCompany(): bool
    {
        return $this->type === OrganizationType::Company;
    }

    /**
     * @return HasMany<QuestionCategory, $this>
     */
    public function questionCategories(): HasMany
    {
        return $this->hasMany(QuestionCategory::class)->orderBy('name');
    }

    /**
     * Every account of the organization: staff, and for an LPK also its members.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Staff accounts (LPK or company), without the LPK's job seekers.
     *
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->where('role', '!=', UserRole::Worker);
    }

    /**
     * Job seekers who registered through this LPK's subdomain.
     *
     * @return HasMany<User, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Worker);
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * The LPKs a company recruits from.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function connectedLpks(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'company_lpk', 'company_id', 'lpk_id')->withTimestamps()->orderBy('name');
    }

    /**
     * The companies an LPK runs test sessions for.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function connectedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'company_lpk', 'lpk_id', 'company_id')->withTimestamps()->orderBy('name');
    }

    /**
     * Determine if this company and the given LPK work together (either way round).
     */
    public function isConnectedTo(Organization $other): bool
    {
        return $this->isCompany()
            ? $this->connectedLpks()->whereKey($other->id)->exists()
            : $this->connectedCompanies()->whereKey($other->id)->exists();
    }

    /**
     * Recruitment requests a company sent to this LPK.
     *
     * @return BelongsToMany<RecruitmentRequest, $this>
     */
    public function receivedRecruitmentRequests(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentRequest::class, 'recruitment_request_lpk', 'lpk_id');
    }

    /**
     * This LPK's price for a payment post, or the default from config when not set.
     */
    public function feeFor(PaymentPurpose $purpose): int
    {
        $fee = match ($purpose) {
            PaymentPurpose::Membership => $this->membership_fee,
            PaymentPurpose::ExamSession => $this->exam_session_fee,
            PaymentPurpose::ExamRegistration => $this->exam_registration_fee,
            PaymentPurpose::InterviewAccess => $this->interview_fee,
            PaymentPurpose::MedicalCheckup => $this->medical_checkup_fee,
        };

        return $fee ?? $purpose->defaultFee();
    }

    /**
     * Who receives this LPK's membership fees.
     */
    public function membershipCollector(): PaymentCollector
    {
        return $this->membership_collected_by ?? PaymentCollector::Careja;
    }

    public function collectsMembershipDirectly(): bool
    {
        return $this->membershipCollector() === PaymentCollector::Lpk;
    }

    public function hasBankAccount(): bool
    {
        return filled($this->bank_name) && filled($this->bank_account_number);
    }

    /**
     * Careja's cut of an amount paid for this LPK, in Rupiah.
     */
    public function carejaShareOf(int $amount): int
    {
        return (int) round($amount * (float) $this->careja_share_percent / 100);
    }

    /**
     * Address on the organization's own subdomain, e.g. https://oki.careja.id/login.
     */
    public function url(string $path = '/'): string
    {
        return app(Tenancy::class)->url($this, $path);
    }

    /**
     * Address shown to people, e.g. "oki.careja.id".
     */
    public function host(): string
    {
        return app(Tenancy::class)->hostFor($this);
    }
}
