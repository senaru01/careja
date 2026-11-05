<?php

namespace App\Notifications;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReviewed extends ActivityNotification
{
    public function __construct(public Payment $payment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->greeting('Halo '.$notifiable->name.',');

        if ($this->payment->status === PaymentStatus::Verified && ! $this->payment->isMembership()) {
            return $message
                ->subject('Pembayaran '.$this->payment->purpose->label().' Berhasil')
                ->line('Pembayaran '.$this->payment->purpose->label().' sebesar Rp '.number_format($this->payment->amount, 0, ',', '.').' telah kami terima.')
                ->line(match ($this->payment->purpose) {
                    PaymentPurpose::InterviewAccess => 'Anda sekarang dapat mengundang kandidat dari peringkat sesi tes untuk wawancara.',
                    PaymentPurpose::MedicalCheckup => 'Perusahaan akan mencatat hasil medical checkup Anda setelah pemeriksaan.',
                    default => 'Terima kasih telah menggunakan Careja.',
                })
                ->action('Buka Dashboard', route('dashboard'));
        }

        if ($this->payment->status === PaymentStatus::Verified) {
            return $message
                ->subject('Pembayaran Pendaftaran Terverifikasi')
                ->line('Pembayaran Anda telah diverifikasi. Profil Anda sekarang masuk ke daftar kandidat dan dapat dilihat oleh LPK dan perusahaan.')
                ->action('Buka Dashboard', route('dashboard'));
        }

        return $message
            ->subject('Pembayaran Pendaftaran Ditolak')
            ->line('Bukti pembayaran Anda ditolak dengan catatan: '.$this->payment->note)
            ->line('Silakan unggah ulang bukti pembayaran yang benar.')
            ->action('Unggah Ulang', route('worker.payment.create'));
    }
}
