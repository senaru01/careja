<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * An email about something that happened in the system (payment, session,
 * interview, recruitment request). These can be switched off with
 * ACTIVITY_EMAILS=false, e.g. while testing with dummy addresses, so the
 * mail provider does not flag the account for bounces. Login codes are not
 * activity emails and are always sent.
 */
abstract class ActivityNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('careja.activity_emails') ? ['mail'] : [];
    }
}
