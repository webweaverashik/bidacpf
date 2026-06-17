<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A single, reusable notification used for every system event surfaced to
 * users — to admins (a batch was created, an advance / recovery / settlement
 * was submitted, a scheduled task finished) and to the originating officer
 * (their submission was approved / rejected / reversed).
 *
 * It fans out to two channels:
 *   - database : drives the header dropdown + the notifications listing page
 *   - mail     : emails the recipient a short alert
 *
 * Construction is centralised in App\Services\NotificationService, so callers
 * never new this up directly. The free-form payload (title/message/url/icon/
 * color/category) keeps the listing UI generic — new event types need no new
 * notification class, only a new call site.
 *
 * Delivery is QUEUED (implements ShouldQueue): NotificationService::dispatch()
 * only pushes a job, so the triggering web request returns immediately and a
 * slow/failing SMTP host can never block — or roll back — a contribution,
 * advance, or settlement action. Both channels (database + mail) are written
 * by the worker, so a queue worker must be running (QUEUE_CONNECTION=database).
 * Jobs are durable and retry on transient failure; permanent failures land in
 * failed_jobs. For local testing without a worker, set QUEUE_CONNECTION=sync.
 */
class SystemEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $category,    // e.g. 'contribution', 'advance', 'recovery', 'settlement', 'scheduled_task'
        public ?string $url = null, // deep link (relative path) to the related record / listing
        public string $icon = 'ki-notification-status',
        public string $color = 'primary', // Metronic contextual: primary|success|warning|danger|info
    ) {
    }

    /**
     * Delivery channels — each gated by config('notifications.channels.*'),
     * so 'database' (in-app) and 'mail' (email) can be toggled independently
     * from .env. An empty result means this notification delivers nothing.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (config('notifications.channels.database', true)) {
            $channels[] = 'database';
        }

        if (config('notifications.channels.mail', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Payload persisted to the notifications table (rendered by the UI).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'    => $this->title,
            'message'  => $this->message,
            'category' => $this->category,
            'url'      => $this->url,
            'icon'     => $this->icon,
            'color'    => $this->color,
        ];
    }

    /**
     * Email representation. Uses Laravel's built-in markdown mail theme, so no
     * extra views need publishing.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[BIDA CPF] ' . $this->title)
            ->greeting('BIDA CPF System')
            ->line($this->message);

        if ($this->url) {
            $mail->action('View Details', url($this->url));
        }

        return $mail->line('This is an automated notification from the BIDA CPF management system.');
    }
}
