<?php

namespace App\Notifications;

use App\Models\Exam;
use App\Models\ExamReschedule;
use Illuminate\Notifications\Messages\MailMessage;

class ExamRescheduled extends ActivityNotification
{
    public function __construct(public Exam $exam, public ExamReschedule $reschedule) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Jadwal Tes Dimundurkan: '.$this->exam->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Jadwal tes "'.$this->exam->title.'" dimundurkan oleh '.$this->exam->organization->name.'.')
            ->line('Jadwal lama: '.$this->reschedule->previous_starts_at->translatedFormat('l, d F Y H:i').' WIB')
            ->line('Jadwal baru: '.$this->reschedule->starts_at->translatedFormat('l, d F Y H:i').' WIB')
            ->line('Alasan: '.$this->reschedule->reason)
            ->line('Jika Anda tidak bisa mengikuti jadwal baru, tekan "Keluar Sesi" sebelum jadwal baru dimulai. Anda akan mendapat 1 token untuk ikut sesi tes lain tanpa bayar lagi. Jika tidak melakukan apa-apa dan tidak hadir, biaya tes hangus.')
            ->action('Lihat Sesi', $this->linkFor($notifiable, 'worker.sessions.show', $this->exam));
    }
}
