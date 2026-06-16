<?php
namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Feeds the header notification dropdown.
 *
 * Bound to the `layouts.partials.header` view, it exposes:
 *   - $headerNotifications : the current user's 10 most recent notifications
 *   - $headerUnreadCount   : count of unread notifications (drives the badge)
 *
 * Notifications are the Laravel database notifications written by
 * App\Notifications\SystemEventNotification; their JSON payload (title,
 * message, url, icon, color) is read straight off each row's `data`.
 */
class NotificationComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            $view->with([
                'headerNotifications' => collect(),
                'headerUnreadCount'   => 0,
            ]);

            return;
        }

        $view->with([
            'headerNotifications' => $user->notifications()->limit(10)->get(),
            'headerUnreadCount'   => $user->unreadNotifications()->count(),
        ]);
    }
}
