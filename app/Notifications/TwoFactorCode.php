<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Login')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Gunakan kode berikut untuk menyelesaikan proses login:')
            ->line('**'.$this->code.'**')
            ->line('Kode ini berlaku selama '.User::TWO_FACTOR_CODE_LIFETIME.' menit.')
            ->line('Jika Anda tidak mencoba login, segera ganti password akun Anda.');
    }
}
