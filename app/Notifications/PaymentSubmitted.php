<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentSubmitted extends ActivityNotification
{
    public function __construct(public Payment $payment) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bukti Pembayaran Baru Menunggu Verifikasi')
            ->line($this->payment->user->name.' ('.$this->payment->user->email.') mengunggah bukti pembayaran '.$this->payment->purpose->label()
                .($this->payment->exam ? ' untuk "'.$this->payment->exam->title.'"' : '').' sebesar Rp '.number_format($this->payment->amount, 0, ',', '.').'.')
            ->action('Verifikasi Pembayaran', route('admin.payments.index'));
    }
}
