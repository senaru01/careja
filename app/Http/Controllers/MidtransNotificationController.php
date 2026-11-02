<?php

namespace App\Http\Controllers;

use App\Services\QrisPayments;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MidtransNotificationController extends Controller
{
    /**
     * Receive Midtrans "Payment Notification URL" callbacks.
     */
    public function __invoke(Request $request, QrisPayments $qris): Response
    {
        if (! $qris->handleNotification($request->all())) {
            return response('Invalid signature', 403);
        }

        return response('OK');
    }
}
