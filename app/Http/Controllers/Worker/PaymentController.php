<?php

namespace App\Http\Controllers\Worker;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StorePaymentRequest;
use App\Models\User;
use App\Notifications\PaymentSubmitted;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Show the membership payment (QRIS or proof upload) and the payment history.
     */
    public function create(Request $request, QrisPayments $qris): View
    {
        $user = $request->user();

        // Bring any open QRIS payment up to date (paid, expired...) before deciding what to show.
        $qris->syncPendingOf($user);

        return view('worker.payment.create', [
            'pendingQris' => $user->payments()->membership()
                ->where('method', PaymentMethod::Midtrans)->where('status', PaymentStatus::Pending)->first(),
            'qrisAvailable' => $qris->isAvailable(),
            'payments' => $user->payments()->with('exam')->get(),
            'fee' => config('careja.registration_fee'),
            'qrisImage' => config('careja.qris_image'),
            'qrisMerchant' => config('careja.qris_merchant_name'),
            'profileComplete' => $user->hasCompleteCandidateProfile(),
            'canUpload' => $user->hasCompleteCandidateProfile() && ! $user->hasPendingPayment() && ! $user->hasActiveMembership(),
            'activeMembership' => $user->activeMembership(),
            'membershipExpired' => $user->hasExpiredMembership(),
            'membershipMonths' => config('careja.membership_months'),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $request->user()->payments()->create([
            'amount' => config('careja.registration_fee'),
            'proof_path' => $request->file('proof')->store('payments', 'local'),
        ]);

        Notification::send(User::superAdmins()->get(), new PaymentSubmitted($payment));

        return redirect()->route('worker.payment.create')
            ->with('status', 'Bukti pembayaran terkirim. Kami akan memverifikasinya secepatnya.');
    }
}
