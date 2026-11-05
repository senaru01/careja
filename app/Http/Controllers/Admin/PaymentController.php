<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ManualPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = PaymentStatus::tryFrom($request->query('status', 'pending'));

        $payments = Payment::query()
            ->with(['user', 'verifier', 'exam'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'status' => $status,
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path, 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    /**
     * Verify or reject a pending payment. A verified payment takes effect
     * right away (membership, session published, seat registered, interviews unlocked).
     */
    public function update(Request $request, Payment $payment, ManualPayments $payments): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return back()->withErrors(['payment' => 'Pembayaran ini sudah diproses.']);
        }

        if ($payment->isMidtrans()) {
            return back()->withErrors(['payment' => 'Pembayaran QRIS otomatis dikonfirmasi oleh Midtrans, bukan manual.']);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(PaymentStatus::class)->only([PaymentStatus::Verified, PaymentStatus::Rejected])],
            'note' => ['nullable', 'required_if:status,'.PaymentStatus::Rejected->value, 'string', 'max:500'],
        ], ['note.required_if' => 'Isi alasan penolakan.']);

        $payments->review($payment, PaymentStatus::from($validated['status']), $request->user(), $validated['note'] ?? null);

        return back()->with('status', "Pembayaran {$payment->user->name} {$payment->status->label()}.");
    }
}
