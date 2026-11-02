<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamSessionPublished extends Notification
{
    public function __construct(public Exam $exam) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->exam;

        return (new MailMessage)
            ->subject('Sesi Tes Baru: '.$exam->position.' — '.$exam->company?->name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($exam->organization->name.' membuka sesi tes "'.$exam->title.'" untuk '.$exam->company?->name.'.')
            ->line('Posisi: '.$exam->position.' · Dibutuhkan: '.$exam->quota.' orang')
            ->line('Kualifikasi: '.$exam->qualifications)
            ->line('Jadwal tes: '.$exam->starts_at->translatedFormat('l, d F Y H:i').' WIB')
            ->line('Biaya ikut tes: Rp '.number_format(config('careja.exam_registration_fee'), 0, ',', '.').'. Daftar hanya jika Anda memenuhi kualifikasi di atas.')
            ->action('Lihat & Daftar', route('worker.sessions.show', $exam));
    }
}
