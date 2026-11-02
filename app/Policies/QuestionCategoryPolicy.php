<?php

namespace App\Policies;

use App\Models\QuestionCategory;
use App\Models\User;

class QuestionCategoryPolicy
{
    /**
     * LPK staff manage the question bank of their own LPK only.
     */
    public function manage(User $user, QuestionCategory $category): bool
    {
        return $user->isLpk() && $user->organization_id === $category->organization_id;
    }
}
