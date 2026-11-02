<?php

namespace App\Models;

use App\Enums\InterviewStatus;
use App\Enums\MedicalStatus;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scheduled_at', 'location', 'notes'])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => InterviewStatus::class,
            'evaluated_at' => 'datetime',
            'medical_status' => MedicalStatus::class,
            'medical_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * The test session the candidate was picked from.
     *
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function medicalChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medical_checked_by');
    }

    public function isEvaluated(): bool
    {
        return $this->status !== InterviewStatus::Scheduled;
    }

    public function needsMedicalCheck(): bool
    {
        return $this->status === InterviewStatus::Passed && $this->medical_status === null;
    }

    /**
     * Final outcome across interview and medical checkup.
     */
    public function outcomeLabel(): string
    {
        return match (true) {
            $this->status === InterviewStatus::Scheduled => 'Wawancara Terjadwal',
            $this->status === InterviewStatus::Failed => 'Tidak Lolos Wawancara',
            $this->medical_status === MedicalStatus::Fit => 'Diterima (Lolos Medical)',
            $this->medical_status === MedicalStatus::Unfit => 'Tidak Lolos Medical',
            default => 'Menunggu Medical Checkup',
        };
    }

    public function outcomeColor(): string
    {
        return match (true) {
            $this->status === InterviewStatus::Scheduled => 'blue',
            $this->status === InterviewStatus::Failed, $this->medical_status === MedicalStatus::Unfit => 'red',
            $this->medical_status === MedicalStatus::Fit => 'green',
            default => 'yellow',
        };
    }
}
