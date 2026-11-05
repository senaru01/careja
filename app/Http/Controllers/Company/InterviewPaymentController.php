<?php

namespace App\Http\Controllers\Company;

use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentProofRequest;
use App\Models\Exam;
use App\Services\ManualPayments;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InterviewPaymentController extends Controller
{
    /**
     * Create the QRIS for the interview fee. Inviting candidates from the
     * session's ranking unlocks as soon as Midtrans confirms the payment.
     */
    public function store(Request $request, Exam $exam, QrisPayments $qris): RedirectResponse
    {
        Gate::authorize('payInterviewAccess', $exam);

        abort_unless($qris->isAvailable(), 503, 'Pembayaran QRIS otomatis belum tersedia.');

        if (! $exam->pendingInterviewAccessPayment() && ! $qris->startInterviewAccess($request->user(), $exam)) {
            return back()->withErrors(['payment' => 'QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi.']);
        }

        return redirect()->route('company.sessions.show', $exam);
    }

    /**
     * Pay the interview fee by transfer and upload the proof. Inviting
     * candidates unlocks once the admin verifies it.
     */
    public function manual(PaymentProofRequest $request, Exam $exam, ManualPayments $payments): RedirectResponse
    {
        Gate::authorize('payInterviewAccess', $exam);

        if ($exam->pendingInterviewAccessPayment()) {
            return back()->withErrors(['payment' => 'Pembayaran pengambilan kandidat sesi ini sedang diproses.']);
        }

        $payments->submit($request->user(), PaymentPurpose::InterviewAccess, $request->file('proof'), $exam);

        return redirect()->route('company.sessions.show', $exam)
            ->with('status', 'Bukti transfer terkirim. Undangan wawancara terbuka setelah admin memverifikasi pembayaran.');
    }
}
