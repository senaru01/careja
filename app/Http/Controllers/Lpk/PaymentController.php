<?php

namespace App\Http\Controllers\Lpk;

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

/**
 * Membership transfers members paid straight to the LPK's own bank account
 * (LPK setting "langsung ke rekening LPK"). The LPK checks its account and
 * verifies them itself, instead of the Careja admin.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = PaymentStatus::tryFrom($request->query('status', 'pending'));

        $payments = Payment::query()
            ->collectedByLpk($request->user()->organization)
            ->with(['user', 'verifier', 'exam'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'status' => $status,
            'statuses' => PaymentStatus::cases(),
            'routePrefix' => 'lpk.payments',
        ]);
    }

    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        $this->ensureCollectedByOwnLpk($request, $payment);

        abort_unless($payment->proof_path, 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    /**
     * Verify (membership becomes active) or reject a pending transfer.
     */
    public function update(Request $request, Payment $payment, ManualPayments $payments): RedirectResponse
    {
        $this->ensureCollectedByOwnLpk($request, $payment);

        if (! $payment->isPending()) {
            return back()->withErrors(['payment' => 'Pembayaran ini sudah diproses.']);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(PaymentStatus::class)->only([PaymentStatus::Verified, PaymentStatus::Rejected])],
            'note' => ['nullable', 'required_if:status,'.PaymentStatus::Rejected->value, 'string', 'max:500'],
        ], ['note.required_if' => 'Isi alasan penolakan.']);

        $payments->review($payment, PaymentStatus::from($validated['status']), $request->user(), $validated['note'] ?? null);

        return back()->with('status', "Pembayaran {$payment->user->name} {$payment->status->label()}.");
    }

    private function ensureCollectedByOwnLpk(Request $request, Payment $payment): void
    {
        abort_unless($payment->isCollectedByLpk() && $payment->lpk_id === $request->user()->organization_id, 404);
    }
}
