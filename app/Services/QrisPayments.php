<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Exam;
use App\Models\Interview;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Payments through Midtrans QRIS (membership, publishing a test session,
 * joining a test session, interview access, medical checkup): create the QR, keep the local payment in sync
 * with Midtrans and hand it to ExamSessions once paid.
 */
class QrisPayments
{
    public function __construct(
        private readonly Midtrans $midtrans,
        private readonly ExamSessions $sessions,
    ) {}

    public function isAvailable(): bool
    {
        return $this->midtrans->isConfigured();
    }

    /**
     * QRIS for the yearly job seeker membership.
     */
    public function startMembership(User $user): ?Payment
    {
        return $this->start($user, PaymentPurpose::Membership, config('careja.registration_fee'),
            'Keanggotaan Careja '.config('careja.membership_months').' bulan');
    }

    /**
     * QRIS the LPK pays to publish a test session.
     */
    public function startExamSession(User $lpkStaff, Exam $exam): ?Payment
    {
        return $this->start($lpkStaff, PaymentPurpose::ExamSession, config('careja.exam_session_fee'),
            'Penerbitan sesi tes: '.$exam->title, $exam);
    }

    /**
     * QRIS a job seeker pays to join a test session.
     */
    public function startExamRegistration(User $worker, Exam $exam): ?Payment
    {
        return $this->start($worker, PaymentPurpose::ExamRegistration, config('careja.exam_registration_fee'),
            'Pendaftaran tes: '.$exam->title, $exam);
    }

    /**
     * QRIS the company pays once per finished session to invite candidates from its ranking.
     */
    public function startInterviewAccess(User $companyStaff, Exam $exam): ?Payment
    {
        return $this->start($companyStaff, PaymentPurpose::InterviewAccess, config('careja.interview_fee'),
            'Pengambilan kandidat: '.$exam->position, $exam);
    }

    /**
     * QRIS a candidate pays for the medical checkup after passing the interview.
     */
    public function startMedicalCheckup(User $worker, Interview $interview): ?Payment
    {
        return $this->start($worker, PaymentPurpose::MedicalCheckup, config('careja.medical_checkup_fee'),
            'Medical checkup: '.$interview->organization->name, $interview->exam, $interview);
    }

    /**
     * A real QRIS charge for the demo page: same price and item as the catalog,
     * but confirming it changes nothing in the recruitment flow.
     */
    public function startDemo(User $demoUser, PaymentPurpose $purpose): ?Payment
    {
        return $this->start($demoUser, $purpose, $purpose->fee(), $purpose->label().' (Demo)', isDemo: true);
    }

    /**
     * Create a pending payment and a QRIS code for it. Returns null when
     * Midtrans refused or could not be reached (the payment is removed).
     */
    public function start(User $user, PaymentPurpose $purpose, int $amount, string $itemName, ?Exam $exam = null, ?Interview $interview = null, bool $isDemo = false): ?Payment
    {
        $payment = $user->payments()->make(['amount' => $amount]);
        $payment->forceFill([
            'purpose' => $purpose,
            'exam_id' => $exam?->id,
            'interview_id' => $interview?->id,
            'is_demo' => $isDemo,
            'method' => PaymentMethod::Midtrans,
            'status' => PaymentStatus::Pending,
        ])->save();
        $payment->forceFill(['gateway_order_id' => 'CRJ-'.$payment->id.'-'.Str::upper(Str::random(6))])->save();

        try {
            $response = $this->midtrans->chargeQris(
                $payment->gateway_order_id,
                $payment->amount,
                $purpose->value,
                Str::limit($itemName, 50, ''),
                ['first_name' => Str::limit($user->name, 50, ''), 'email' => $user->email],
                config('services.midtrans.qris_expiry_minutes'),
            );
        } catch (Throwable $exception) {
            Log::warning('Midtrans QRIS charge failed', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);
            $payment->delete();

            return null;
        }

        if (($response['status_code'] ?? null) !== '201') {
            Log::warning('Midtrans QRIS charge rejected', ['payment_id' => $payment->id, 'response' => $response]);
            $payment->delete();

            return null;
        }

        $qrUrl = collect($response['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        $payment->forceFill([
            'gateway_transaction_id' => $response['transaction_id'] ?? null,
            'gateway_status' => $response['transaction_status'] ?? 'pending',
            'qr_url' => $qrUrl,
            'qr_string' => $response['qr_string'] ?? null,
            // Midtrans reports times in WIB; store them in the app's timezone.
            'payment_due_at' => isset($response['expiry_time'])
                ? Carbon::parse($response['expiry_time'], 'Asia/Jakarta')->setTimezone(config('app.timezone'))
                : now()->addMinutes(config('services.midtrans.qris_expiry_minutes')),
        ])->save();

        return $payment;
    }

    /**
     * Ask Midtrans for the latest status of a pending QRIS payment and apply it.
     */
    public function sync(Payment $payment): Payment
    {
        if (! $payment->isMidtrans() || ! $payment->gateway_order_id
            || ! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Expired], true)) {
            return $payment;
        }

        try {
            $transaction = $this->midtrans->status($payment->gateway_order_id);
        } catch (Throwable $exception) {
            Log::warning('Midtrans status check failed', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);

            return $payment;
        }

        // Unknown at Midtrans and already past due: nothing can be paid any more.
        if (($transaction['status_code'] ?? null) === '404' && $payment->payment_due_at?->isPast()) {
            $transaction = ['transaction_status' => 'expire'];
        }

        $this->apply($payment, $transaction);

        return $payment->refresh();
    }

    /**
     * Bring every open QRIS payment of the user up to date.
     */
    public function syncPendingOf(User $user): void
    {
        $user->payments()
            ->where('method', PaymentMethod::Midtrans)
            ->where('status', PaymentStatus::Pending)
            ->get()
            ->each(fn (Payment $payment) => $this->sync($payment));
    }

    /**
     * Handle an HTTP notification from Midtrans. The signature is checked and the
     * status is then re-read from Midtrans, so a forged body can not activate anything.
     *
     * @param  array<string, mixed>  $notification
     */
    public function handleNotification(array $notification): bool
    {
        if (! $this->midtrans->hasValidSignature($notification)) {
            return false;
        }

        $payment = Payment::where('gateway_order_id', $notification['order_id'])->first();

        if ($payment) {
            $this->sync($payment);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function apply(Payment $payment, array $transaction): void
    {
        // The row lock makes sure a payment is fulfilled exactly once, even when
        // the webhook and the polling page arrive at the same moment.
        $notify = DB::transaction(function () use ($payment, $transaction) {
            $locked = Payment::query()->lockForUpdate()->find($payment->id);

            if (! $locked?->applyGatewayStatus($transaction) || $locked->is_demo) {
                return null;
            }

            return $this->sessions->fulfil($locked);
        });

        if ($notify) {
            $notify();
        }
    }
}
