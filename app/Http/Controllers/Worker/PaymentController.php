<?php

namespace App\Http\Controllers\Worker;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StorePaymentRequest;
use App\Services\ManualPayments;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Show the membership payment (QRIS or proof upload) and the payment
     * history. Price and where to pay follow the member's LPK.
     */
    public function create(Request $request, QrisPayments $qris): View
    {
        $user = $request->user();
        $lpk = $user->organization;

        // Bring any open QRIS payment up to date (paid, expired...) before deciding what to show.
        $qris->syncPendingOf($user);

        return view('worker.payment.create', [
            'pendingQris' => $user->payments()->membership()
                ->where('method', PaymentMethod::Midtrans)->where('status', PaymentStatus::Pending)->first(),
            'qrisAvailable' => $qris->isAvailableForMembership($user),
            'payments' => $user->payments()->with('exam')->get(),
            'fee' => PaymentPurpose::Membership->fee($lpk),
            'examFee' => PaymentPurpose::ExamRegistration->fee($lpk),
            'lpk' => $lpk,
            'paysLpkDirectly' => (bool) $lpk?->collectsMembershipDirectly(),
            'qrisImage' => config('careja.qris_image'),
            'qrisMerchant' => config('careja.qris_merchant_name'),
            'profileComplete' => $user->hasCompleteCandidateProfile(),
            'canUpload' => $user->hasCompleteCandidateProfile() && ! $user->hasPendingPayment() && ! $user->hasActiveMembership(),
            'activeMembership' => $user->activeMembership(),
            'membershipExpired' => $user->hasExpiredMembership(),
            'membershipMonths' => config('careja.membership_months'),
        ]);
    }

    public function store(StorePaymentRequest $request, ManualPayments $payments): RedirectResponse
    {
        $payment = $payments->submit($request->user(), PaymentPurpose::Membership, $request->file('proof'));

        return redirect()->route('worker.payment.create')
            ->with('status', $payment->isCollectedByLpk()
                ? 'Bukti pembayaran terkirim. LPK Anda akan memverifikasinya secepatnya.'
                : 'Bukti pembayaran terkirim. Kami akan memverifikasinya secepatnya.');
    }
}
