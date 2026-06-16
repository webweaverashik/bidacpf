<?php
namespace App\Services;

use App\Models\Auth\User;
use App\Notifications\SystemEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Central delivery point for system notifications. Its single responsibility
 * is *delivery* — message content (titles/wording/links) is built by each
 * domain at the trigger site, keeping this service small and reusable.
 *
 * Two directions:
 *   notifyAdmins() — officer → admin: a batch was created, an advance /
 *                    recovery / settlement was submitted, a scheduled task
 *                    finished, etc. Goes to every active Admin.
 *   notifyUser()   — admin → officer: the officer who owns a record is told
 *                    it was approved / rejected / reversed.
 *
 * Every send is wrapped so a mail/notification failure can never abort the
 * domain action that triggered it (mirrors ScheduledTaskLogger's defensive
 * logging). Delivery is synchronous; see SystemEventNotification for how to
 * move it onto the queue later.
 */
class NotificationService
{
    /**
     * Notify every active Admin. Optionally skip one user — typically the
     * actor, when an Admin performed the action themselves and need not be
     * told about their own work.
     */
    public function notifyAdmins(
        string $title,
        string $message,
        string $category,
        ?string $url = null,
        string $icon = 'ki-notification-status',
        string $color = 'primary',
        ?int $exceptUserId = null,
    ): void {
        $admins = User::role('Admin')
            ->where('is_active', true)
            ->when($exceptUserId, fn($q) => $q->whereKeyNot($exceptUserId))
            ->get();

        $this->dispatch($admins, $title, $message, $category, $url, $icon, $color);
    }

    /**
     * Notify one or more specific users (the related officer(s) — typically the
     * record's submitter AND its creator, which may differ). Accepts a User, an
     * id, or an array/Collection of either; nulls and duplicates are dropped, so
     * callers can pass [$x->submitted_by, $x->created_by] without guarding. An
     * empty/all-null set is silently skipped.
     */
    public function notifyUser(
        User | int | array | Collection | null $user,
        string $title,
        string $message,
        string $category,
        ?string $url = null,
        string $icon = 'ki-notification-status',
        string $color = 'primary',
    ): void {
        $list = match (true) {
            is_null($user)              => [],
            is_array($user)             => $user,
            $user instanceof Collection => $user->all(),
            default                     => [$user],
        };

        // Normalise to a unique set of user ids (User models or raw ids; nulls dropped).
        $ids = collect($list)
            ->map(fn($u) => $u instanceof User ? $u->getKey() : $u)
            ->reject(fn($id) => is_null($id))
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $recipients = User::whereKey($ids)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->dispatch($recipients, $title, $message, $category, $url, $icon, $color);
    }

    /**
     * Build the notification once and fan it out to the given recipients.
     */
    protected function dispatch(
        Collection $recipients,
        string $title,
        string $message,
        string $category,
        ?string $url,
        string $icon,
        string $color,
    ): void {
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $recipients,
                new SystemEventNotification($title, $message, $category, $url, $icon, $color),
            );
        } catch (\Throwable $e) {
            Log::warning('NotificationService could not deliver "' . $title . '": ' . $e->getMessage());
        }
    }
}
