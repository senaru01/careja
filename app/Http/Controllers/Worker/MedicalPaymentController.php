<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MedicalPaymentController extends Controller
{
    /**
     * Create the QRIS for the medical checkup fee of a passed interview.
     */
    public function store(Request $request, Interview $interview, QrisPayments $qris): RedirectResponse
    {
        Gate::authorize('payMedical', $interview);

        abort_unless($qris->isAvailable(), 503, 'Pembayaran QRIS otomatis belum tersedia.');

        if (! $interview->pendingMedicalPayment() && ! $qris->startMedicalCheckup($request->user(), $interview)) {
            return redirect()->route('dashboard')->withErrors(['payment' => 'QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi.']);
        }

        return redirect()->route('dashboard');
    }
}
