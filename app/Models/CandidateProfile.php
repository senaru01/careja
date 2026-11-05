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

    /**
     * WhatsApp chat link for the candidate's phone (08xx becomes 628xx), or
     * null when no usable number is filled.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if (strlen($digits) < 8) {
            return null;
        }

        return 'https://wa.me/'.(str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits);
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
