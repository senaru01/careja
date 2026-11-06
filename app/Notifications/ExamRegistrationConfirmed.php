<?php

namespace App\Notifications;

use App\Models\ExamAttempt;
use Illuminate\Notifications\Messages\MailMessage;

class ExamRegistrationConfirmed extends ActivityNotification
{
    public function __construct(public ExamAttempt $attempt) {}

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->attempt->exam;

        return (new MailMessage)
            ->subject('Pendaftaran Tes Berhasil: '.$exam->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Pembayaran Anda diterima. Anda terdaftar sebagai peserta tes "'.$exam->title.'".')
            ->line('Tes dibuka: '.$exam->starts_at->translatedFormat('l, d F Y H:i').' s.d. '.$exam->ends_at->translatedFormat('d F Y H:i').' WIB')
            ->line('Durasi: '.$exam->duration_minutes.' menit. Nilai lulus minimal: '.$exam->passing_score.'.')
            ->action('Lihat Sesi', $this->linkFor($notifiable, 'worker.sessions.show', $exam));
    }
}
