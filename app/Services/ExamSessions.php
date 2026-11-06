<?php

namespace App\Services;

use App\Enums\PaymentPurpose;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamPosition;
use App\Models\ExamReschedule;
use App\Models\ExamToken;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ExamRegistrationConfirmed;
use App\Notifications\ExamRescheduled;
use App\Notifications\ExamSessionPublished;
use App\Notifications\PaymentReviewed;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The rules of a test session: publishing, registering, postponing and
 * leaving with a token.
 */
class ExamSessions
{
    /**
     * Apply a payment that was just confirmed. Runs inside the payment's
     * locked transaction; returns the notification step to run after commit.
     */
    public function fulfil(Payment $payment): ?Closure
    {
        return match ($payment->purpose) {
            PaymentPurpose::ExamSession => $this->publish($payment),
            PaymentPurpose::ExamRegistration => $this->confirmRegistration($payment),
            default => fn () => $payment->user->notify(new PaymentReviewed($payment)),
        };
    }

    /**
     * The LPK paid: the session goes live and every active member of that LPK is told.
     */
    private function publish(Payment $payment): ?Closure
    {
        $exam = $payment->exam;

        if (! $exam || $exam->isPublished()) {
            return null;
        }

        $exam->forceFill(['published_at' => now()])->save();

        return fn () => Notification::send(
            User::activeCandidates()->where('organization_id', $exam->organization_id)->get(),
            new ExamSessionPublished($exam),
        );
    }

    /**
     * The job seeker paid for a seat. Money is never lost: if the seat can no
     * longer be used (session over, already registered, left), a token is
     * issued instead.
     */
    private function confirmRegistration(Payment $payment): ?Closure
    {
        $exam = $payment->exam;
        $attempt = $exam?->attempts()->where('user_id', $payment->user_id)->lockForUpdate()->first();

        if ($attempt && $attempt->registered_at === null && ! $exam->hasEnded()) {
            $attempt->forceFill(['registered_at' => now()])->save();

            return fn () => $payment->user->notify(new ExamRegistrationConfirmed($attempt->setRelation('exam', $exam)));
        }

        $this->issueToken($payment->user, $attempt, 'Pembayaran tes diterima saat kursi tidak bisa dipakai lagi');

        return null;
    }

    /**
     * Start (or resume) a registration for one of the session's positions:
     * the seat is held but not yet paid.
     */
    public function prepareRegistration(User $worker, Exam $exam, ?ExamPosition $position = null): ExamAttempt
    {
        $attempt = $exam->attempts()->where('user_id', $worker->id)->first() ?? $exam->attempts()->make();
        $attempt->forceFill([
            'user_id' => $worker->id,
            'exam_position_id' => $position?->id,
            'qualification_confirmed_at' => now(),
        ])->save();

        return $attempt;
    }

    /**
     * Pay for the seat with one of the worker's tokens.
     */
    public function registerWithToken(ExamAttempt $attempt): bool
    {
        return DB::transaction(function () use ($attempt) {
            $token = ExamToken::query()->lockForUpdate()
                ->where('user_id', $attempt->user_id)
                ->available()
                ->oldest('id')
                ->first();

            if (! $token) {
                return false;
            }

            $token->forceFill(['used_at' => now(), 'used_attempt_id' => $attempt->id])->save();
            $attempt->forceFill(['registered_at' => now()])->save();

            return true;
        });
    }

    /**
     * Leave a postponed session; the seat turns into one token.
     */
    public function withdraw(ExamAttempt $attempt): ExamToken
    {
        return DB::transaction(function () use ($attempt) {
            $attempt->forceFill(['withdrawn_at' => now()])->save();

            return $this->issueToken($attempt->user, $attempt, 'Keluar dari sesi "'.$attempt->exam->title.'" yang dimundurkan');
        });
    }

    /**
     * Postpone the session. The change is kept as public history and every
     * participant is told they may leave for a token before the new start.
     */
    public function reschedule(Exam $exam, User $by, CarbonInterface $startsAt, CarbonInterface $endsAt, string $reason): ExamReschedule
    {
        $reschedule = DB::transaction(function () use ($exam, $by, $startsAt, $endsAt, $reason) {
            $reschedule = $exam->reschedules()->make();
            $reschedule->forceFill([
                'rescheduled_by' => $by->id,
                'previous_starts_at' => $exam->starts_at,
                'previous_ends_at' => $exam->ends_at,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $reason,
            ])->save();

            $exam->forceFill(['starts_at' => $startsAt, 'ends_at' => $endsAt])->save();

            return $reschedule;
        });

        $participants = User::whereIn('id', $exam->participants()->select('user_id'))->get();
        Notification::send($participants, new ExamRescheduled($exam, $reschedule));

        return $reschedule;
    }

    private function issueToken(User $user, ?ExamAttempt $source, string $note): ExamToken
    {
        $token = $user->examTokens()->make();
        $token->forceFill(['source_attempt_id' => $source?->id, 'note' => $note])->save();

        return $token;
    }
}
