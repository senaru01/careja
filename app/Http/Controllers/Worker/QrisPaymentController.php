<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QrisPaymentController extends Controller
{
    /**
     * Generate a Midtrans QRIS code for the membership fee.
     */
    public function store(Request $request, QrisPayments $qris): RedirectResponse
    {
        $user = $request->user();

        abort_unless($qris->isAvailableForMembership($user), 503, 'Pembayaran QRIS otomatis belum tersedia.');
        abort_unless(
            $user->hasCompleteCandidateProfile() && ! $user->hasPendingPayment() && ! $user->hasActiveMembership(),
            403,
        );

        if (! $qris->startMembership($user)) {
            return redirect()->route('worker.payment.create')
                ->withErrors(['payment' => 'QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi, atau gunakan upload bukti manual.']);
        }

        return redirect()->route('worker.payment.create');
    }
}
