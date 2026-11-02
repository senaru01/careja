<?php

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use Database\Factories\CandidateProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nik', 'phone', 'gender', 'birth_place', 'birth_date', 'address', 'city', 'province',
    'education_level', 'education_institution', 'education_major', 'graduation_year', 'summary',
])]
class CandidateProfile extends Model
{
    /** @use HasFactory<CandidateProfileFactory> */
    use HasFactory;

    /**
     * Fields that must be filled before the candidate may pay the registration fee.
     *
     * @var list<string>
     */
    public const REQUIRED_FIELDS = [
        'nik', 'phone', 'gender', 'birth_place', 'birth_date', 'address', 'city', 'province',
        'education_level', 'education_institution', 'graduation_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'education_level' => EducationLevel::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (blank($this->{$field})) {
                return false;
            }
        }

        return true;
    }
}
