<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmitted extends Notification
{
    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bukti Pembayaran Baru Menunggu Verifikasi')
            ->line($this->payment->user->name.' ('.$this->payment->user->email.') mengunggah bukti pembayaran pendaftaran.')
            ->action('Verifikasi Pembayaran', route('admin.payments.index'));
    }
}
