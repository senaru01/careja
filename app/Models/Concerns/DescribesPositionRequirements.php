<?php

namespace App\Models\Concerns;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Models\User;

/**
 * A row of a position table (company request or test session): the job,
 * how many people are needed, and the minimum education, age range and
 * work experience a job seeker must have to apply.
 *
 * @property string $position
 * @property int $quota
 * @property Gender|null $gender
 * @property EducationLevel|null $min_education
 * @property int|null $min_age
 * @property int|null $max_age
 * @property int $min_experience_years
 * @property string|null $notes
 */
trait DescribesPositionRequirements
{
    /**
     * Columns copied when a request becomes a session.
     *
     * @var list<string>
     */
    public const REQUIREMENT_FIELDS = ['position', 'quota', 'gender', 'min_education', 'min_age', 'max_age', 'min_experience_years', 'notes'];

    public function initializeDescribesPositionRequirements(): void
    {
        $this->mergeFillable(self::REQUIREMENT_FIELDS);
        $this->mergeCasts([
            'quota' => 'integer',
            'gender' => Gender::class,
            'min_education' => EducationLevel::class,
            'min_age' => 'integer',
            'max_age' => 'integer',
            'min_experience_years' => 'integer',
        ]);
    }

    public function genderLabel(): string
    {
        return $this->gender?->label() ?? 'Laki-laki / Perempuan';
    }

    public function educationLabel(): string
    {
        return $this->min_education?->minimumLabel() ?? 'Semua pendidikan';
    }

    public function ageLabel(): string
    {
        return match (true) {
            $this->min_age && $this->max_age => "{$this->min_age}–{$this->max_age} tahun",
            (bool) $this->min_age => "Min. {$this->min_age} tahun",
            (bool) $this->max_age => "Maks. {$this->max_age} tahun",
            default => 'Semua umur',
        };
    }

    public function experienceLabel(): string
    {
        return $this->min_experience_years > 0
            ? "Pengalaman min. {$this->min_experience_years} tahun"
            : 'Fresh graduate boleh';
    }

    /**
     * One line summary, e.g. "Laki-laki · Min. SMA/SMK · 18–30 tahun · Fresh graduate boleh".
     */
    public function requirementsSummary(): string
    {
        return implode(' · ', [$this->genderLabel(), $this->educationLabel(), $this->ageLabel(), $this->experienceLabel()]);
    }

    /**
     * Requirements the job seeker does not meet, empty when they may apply.
     *
     * @return list<string>
     */
    public function unmetRequirements(User $worker): array
    {
        $profile = $worker->candidateProfile;
        $age = $profile?->birth_date?->age;
        $problems = [];

        if ($this->gender && $profile?->gender !== $this->gender) {
            $problems[] = 'Khusus '.$this->gender->label();
        }

        if ($this->min_education && ! $profile?->education_level?->meets($this->min_education)) {
            $problems[] = 'Pendidikan '.$this->min_education->minimumLabel();
        }

        if (($this->min_age && ($age === null || $age < $this->min_age)) || ($this->max_age && ($age === null || $age > $this->max_age))) {
            $problems[] = 'Umur '.$this->ageLabel().($age !== null ? " (umur Anda {$age})" : '');
        }

        if ($this->min_experience_years > 0 && $worker->workExperienceYears() < $this->min_experience_years) {
            $problems[] = $this->experienceLabel().' (Anda '.$worker->workExperienceLabel().')';
        }

        return $problems;
    }
}
