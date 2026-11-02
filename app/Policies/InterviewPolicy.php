<?php

namespace App\Policies;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InterviewPolicy
{
    /**
     * Only staff of the company that scheduled the interview can record its
     * result, and only once so the decision can not be changed afterwards.
     */
    public function evaluate(User $user, Interview $interview): Response
    {
        if (! $this->owns($user, $interview)) {
            return Response::deny();
        }

        return $interview->isEvaluated()
            ? Response::deny('Hasil wawancara sudah dicatat dan tidak dapat diubah.')
            : Response::allow();
    }

    /**
     * The final medical checkup is recorded once, for candidates that passed the interview.
     */
    public function recordMedical(User $user, Interview $interview): Response
    {
        if (! $this->owns($user, $interview)) {
            return Response::deny();
        }

        return $interview->needsMedicalCheck()
            ? Response::allow()
            : Response::deny('Medical checkup hanya untuk kandidat yang lulus wawancara dan belum pernah dicatat.');
    }

    private function owns(User $user, Interview $interview): bool
    {
        return $user->isCompany() && $user->organization_id === $interview->organization_id;
    }
}
