<?php

namespace App\Notifications;

use App\Models\RecruitmentRequest;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells LPK staff that a company needs new employees.
 */
class RecruitmentRequested extends ActivityNotification
{
    public function __construct(public RecruitmentRequest $recruitment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $recruitment = $this->recruitment->loadMissing(['company', 'positions']);

        $message = (new MailMessage)
            ->subject('Permintaan Rekrutmen Baru: '.$recruitment->company->name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($recruitment->company->name.' membutuhkan karyawan baru ("'.$recruitment->title.'"):');

        foreach ($recruitment->positions as $position) {
            $message->line('• '.$position->position.' — '.$position->quota.' orang ('.$position->requirementsSummary().')');
        }

        if ($recruitment->needed_by) {
            $message->line('Target mulai kerja: '.$recruitment->needed_by->translatedFormat('d F Y'));
        }

        return $message->action('Buat Sesi Tes', $this->linkFor($notifiable, 'lpk.recruitments.index'));
    }
}
