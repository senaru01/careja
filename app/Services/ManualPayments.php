<?php

namespace App\Services;

use App\Enums\PaymentCollector;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Exam;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use App\Notifications\PaymentSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Payments by transfer with an uploaded proof, for when QRIS through
 * Midtrans is not available. The admin verifies the proof; a verified
 * payment is fulfilled exactly like a confirmed QRIS payment.
 */
class ManualPayments
{
    public function __construct(private readonly ExamSessions $sessions) {}

    /**
     * Store the proof as a pending payment, at the LPK's price, and tell
     * whoever verifies it: the LPK itself when its members pay the membership
     * straight to the LPK's account, the Careja admins otherwise.
     */
    public function submit(User $payer, PaymentPurpose $purpose, UploadedFile $proof, ?Exam $exam = null): Payment
    {
        $lpk = $exam?->organization ?? ($purpose === PaymentPurpose::Membership ? $payer->organization : null);
        $collector = $purpose === PaymentPurpose::Membership && $lpk?->collectsMembershipDirectly()
            ? PaymentCollector::Lpk
            : PaymentCollector::Careja;

        $payment = $payer->payments()->make([
            'amount' => $purpose->fee($lpk),
            'proof_path' => $proof->store('payments', 'local'),
        ]);
        $payment->forceFill([
            'purpose' => $purpose,
            'exam_id' => $exam?->id,
            'lpk_id' => $lpk?->id,
            'method' => PaymentMethod::Manual,
            'collected_by' => $collector,
            'status' => PaymentStatus::Pending,
        ])->save();

        $verifiers = $collector === PaymentCollector::Lpk
            ? $lpk->staff()->whereNotNull('approved_at')->get()
            : User::superAdmins()->get();

        Notification::send($verifiers, new PaymentSubmitted($payment));

        return $payment;
    }

    /**
     * Apply the admin's decision. A verified payment publishes the session,
     * registers the participant, unlocks interviews or activates the membership.
     */
    public function review(Payment $payment, PaymentStatus $status, User $admin, ?string $note = null): void
    {
        $notify = DB::transaction(function () use ($payment, $status, $admin, $note) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $locked->isPending()) {
                return null;
            }

            $locked->markAs($status, $admin, $note);

            return $status === PaymentStatus::Verified
                ? $this->sessions->fulfil($locked)
                : fn () => $locked->user->notify(new PaymentReviewed($locked));
        });

        $payment->refresh();

        if ($notify) {
            $notify();
        }
    }
}
