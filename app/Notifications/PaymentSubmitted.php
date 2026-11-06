<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentSubmitted extends ActivityNotification
{
    public function __construct(public Payment $payment) {}

    public function toMail(object $notifiable): MailMessage
    {
        // Proofs paid straight to the LPK are verified by the LPK, the rest by the Careja admin.
        $url = $this->linkFor($notifiable, $this->payment->isCollectedByLpk() ? 'lpk.payments.index' : 'admin.payments.index');

        return (new MailMessage)
            ->subject('Bukti Pembayaran Baru Menunggu Verifikasi')
            ->line($this->payment->user->name.' ('.$this->payment->user->email.') mengunggah bukti pembayaran '.$this->payment->purpose->label()
                .($this->payment->exam ? ' untuk "'.$this->payment->exam->title.'"' : '').' sebesar Rp '.number_format($this->payment->amount, 0, ',', '.').'.')
            ->action('Verifikasi Pembayaran', $url);
    }
}
