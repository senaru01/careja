<?php

namespace App\Models;

use App\Models\Concerns\DescribesPositionRequirements;
use Database\Factories\RecruitmentPositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A position a company asks for in a recruitment request.
 */
class RecruitmentPosition extends Model
{
    /** @use HasFactory<RecruitmentPositionFactory> */
    use DescribesPositionRequirements, HasFactory;

    /**
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }
}
