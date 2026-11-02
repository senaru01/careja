<?php

namespace App\Models;

use Database\Factories\QuestionCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class QuestionCategory extends Model
{
    /** @use HasFactory<QuestionCategoryFactory> */
    use HasFactory;

    /**
     * Categories every new LPK starts with.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'Psikotes' => 'Tes psikologi: logika, numerik, verbal, dan kepribadian.',
        'Pengetahuan Umum' => 'Wawasan umum, kewarganegaraan, dan pengetahuan dasar.',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<BankQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(BankQuestion::class);
    }
}
