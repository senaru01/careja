<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\QrisPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    /**
     * Polled by every QRIS page: re-check the payment at Midtrans.
     */
    public function __invoke(Request $request, Payment $payment, QrisPayments $qris): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->isMidtrans(), 404);

        $payment = $qris->sync($payment);

        return response()->json([
            'status' => $payment->status->value,
            'active' => $payment->isActive(),
        ]);
    }
}
