<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
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
 * Delivery is synchronous (no ShouldQueue) so the in-app entry appears the
 * instant the action completes — matching the OTP mail flow. To move delivery
 * onto the queue later, add `implements ShouldQueue` to the class signature;
 * the database row will then also be written by the worker.
 */
class SystemEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $category,          // e.g. 'contribution', 'advance', 'recovery', 'settlement', 'scheduled_task'
        public ?string $url = null,       // deep link (relative path) to the related record / listing
        public string $icon = 'ki-notification-status',
        public string $color = 'primary', // Metronic contextual: primary|success|warning|danger|info
    ) {
    }

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
