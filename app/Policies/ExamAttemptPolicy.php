<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamAttemptPolicy
{
    /**
     * Only the participant with a paid seat can open the exam.
     */
    public function take(User $user, ExamAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id && $attempt->isRegistered();
    }

    /**
     * Leaving for a token is only possible after the schedule was postponed.
     */
    public function withdraw(User $user, ExamAttempt $attempt): Response
    {
        if ($user->id !== $attempt->user_id) {
            return Response::denyAsNotFound();
        }

        return $attempt->canWithdraw()
            ? Response::allow()
            : Response::deny('Keluar sesi hanya bisa dilakukan jika jadwal tes dimundurkan setelah Anda mendaftar, dan sebelum jadwal baru dimulai.');
    }

    /**
     * Only staff of the LPK that owns the exam can write the evaluation.
     */
    public function evaluate(User $user, ExamAttempt $attempt): bool
    {
        return $user->isLpk()
            && $user->organization_id === $attempt->exam->organization_id
            && $attempt->isSubmitted();
    }
}
