<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification
{
    public function __construct(public Interview $interview) {}

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
            ->subject('Undangan Wawancara dari '.$this->interview->organization->name)
            ->greeting('Selamat '.$notifiable->name.',')
            ->line('Anda lolos tes teori dan diundang wawancara oleh '.$this->interview->organization->name.'.')
            ->line('Jadwal: '.$this->interview->scheduled_at->translatedFormat('l, d F Y H:i'))
            ->line('Tempat: '.$this->interview->location)
            ->action('Lihat Detail', route('dashboard'));
    }
}
