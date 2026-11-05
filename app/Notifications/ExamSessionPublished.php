<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Messages\MailMessage;

class ExamSessionPublished extends ActivityNotification
{
    public function __construct(public Exam $exam) {}

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->exam;

        $message = (new MailMessage)
            ->subject('Sesi Tes Baru: '.$exam->position.' — '.$exam->company?->name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($exam->organization->name.' membuka sesi tes "'.$exam->title.'" untuk '.$exam->company?->name.'.');

        if ($exam->positions->isEmpty()) {
            $message->line('Posisi: '.$exam->position.' · Dibutuhkan: '.$exam->quota.' orang');
        }

        foreach ($exam->positions as $position) {
            $message->line('• '.$position->position.' — '.$position->quota.' orang ('.$position->requirementsSummary().')');
        }

        if (filled($exam->qualifications)) {
            $message->line('Kualifikasi: '.$exam->qualifications);
        }

        return $message
            ->line('Jadwal tes: '.$exam->starts_at->translatedFormat('l, d F Y H:i').' WIB (tes dikerjakan bersama di ruangan, dimulai oleh pengawas LPK)')
            ->line('Biaya ikut tes: Rp '.number_format(config('careja.exam_registration_fee'), 0, ',', '.').'. Daftar hanya jika Anda memenuhi kualifikasi di atas.')
            ->action('Lihat & Daftar', route('worker.sessions.show', $exam));
    }
}
