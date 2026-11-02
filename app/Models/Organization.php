<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'email', 'phone', 'address', 'description'])]
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
        ];
    }

    /**
     * @return HasMany<QuestionCategory, $this>
     */
    public function questionCategories(): HasMany
    {
        return $this->hasMany(QuestionCategory::class)->orderBy('name');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
}
