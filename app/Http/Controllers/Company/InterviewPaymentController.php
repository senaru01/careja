<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Exam;
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
}
