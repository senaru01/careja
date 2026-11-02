<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExamPublicationController extends Controller
{
    /**
     * Create the QRIS for the session fee. The session is published as soon
     * as Midtrans confirms the payment.
     */
    public function store(Request $request, Exam $exam, QrisPayments $qris): RedirectResponse
    {
        Gate::authorize('publish', $exam);

        abort_unless($qris->isAvailable(), 503, 'Pembayaran QRIS otomatis belum tersedia.');

        if (! $exam->pendingPublicationPayment() && ! $qris->startExamSession($request->user(), $exam)) {
            return back()->withErrors(['payment' => 'QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi.']);
        }

        return redirect()->route('lpk.exams.show', $exam);
    }
}
