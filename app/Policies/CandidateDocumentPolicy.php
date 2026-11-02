<?php

namespace App\Policies;

use App\Models\CandidateDocument;
use App\Models\User;

class CandidateDocumentPolicy
{
    /**
     * Documents follow the same visibility as the candidate's profile.
     */
    public function view(User $user, CandidateDocument $document): bool
    {
        return $document->user->isCandidateVisibleTo($user);
    }

    public function delete(User $user, CandidateDocument $document): bool
    {
        return $user->id === $document->user_id;
    }
}
