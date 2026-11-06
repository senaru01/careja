<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\Tenancy;
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

    /**
     * Link to a page on the recipient's own site: their LPK's or company's
     * subdomain, or the root domain for the super admin, since every account
     * only logs in on its own address.
     *
     * @param  mixed  $parameters
     */
    protected function linkFor(object $notifiable, string $routeName, $parameters = []): string
    {
        $site = $notifiable instanceof User && ! $notifiable->isSuperAdmin() ? $notifiable->organization : null;

        return app(Tenancy::class)->url($site, route($routeName, $parameters, absolute: false));
    }
}
