<?php

namespace App\Models;

use Database\Factories\RecruitmentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A company's request for new employees: the positions it needs and who
 * may apply. It goes to the connected LPKs the company picked; one of them
 * follows it up by turning it into a test session.
 */
#[Fillable(['title', 'description', 'needed_by'])]
class RecruitmentRequest extends Model
{
    /** @use HasFactory<RecruitmentRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'needed_by' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
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
     * @return HasMany<RecruitmentPosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(RecruitmentPosition::class)->orderBy('id');
    }

    /**
     * Test sessions the LPK made from this request.
     *
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * The LPKs the company sent this request to (chosen from its connected LPKs).
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function lpks(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'recruitment_request_lpk', 'recruitment_request_id', 'lpk_id')->orderBy('name');
    }

    /**
     * Scope a query to requests sent to the given LPK.
     *
     * @param  Builder<RecruitmentRequest>  $query
     */
    #[Scope]
    protected function sentTo(Builder $query, Organization $lpk): void
    {
        $query->whereHas('lpks', fn (Builder $lpks) => $lpks->whereKey($lpk->id));
    }

    public function isSentTo(?Organization $lpk): bool
    {
        return $lpk !== null && $this->lpks()->whereKey($lpk->id)->exists();
    }

    /**
     * Scope a query to requests the company has not closed.
     *
     * @param  Builder<RecruitmentRequest>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Determine if an LPK already made a session from this request.
     */
    public function isProcessed(): bool
    {
        return (bool) ($this->exams_count ?? $this->exams()->exists());
    }

    /**
     * The company may change the request until an LPK picks it up.
     */
    public function isEditable(): bool
    {
        return ! $this->isClosed() && ! $this->isProcessed();
    }

    public function totalQuota(): int
    {
        return (int) $this->positions->sum('quota');
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isClosed() => 'Ditutup',
            $this->isProcessed() => 'Ditindaklanjuti LPK',
            default => 'Menunggu LPK',
        };
    }

    public function statusColor(): string
    {
        return match (true) {
            $this->isClosed() => 'gray',
            $this->isProcessed() => 'green',
            default => 'yellow',
        };
    }

    /**
     * Replace the position table.
     *
     * @param  array<int, array<string, mixed>>  $positions
     */
    public function syncPositions(array $positions): void
    {
        DB::transaction(function () use ($positions) {
            $this->positions()->delete();
            $this->positions()->createMany($positions);
        });

        $this->unsetRelation('positions');
    }
}
