<?php
namespace App\Providers;

use App\Listeners\ScheduledTaskLogger;
use App\Models\Setting;
use App\View\Composers\NotificationComposer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(ScheduledTaskLogger::class);
        View::composer('layouts.partials.header', NotificationComposer::class);

        $this->bootRuntimeSettings();
    }

    /**
     * Bridge DB-managed feature flags into runtime config.
     *
     * The settings table is the source of truth for these switches; the env
     * defaults baked into config/otp.php and config/notifications.php act only
     * as the fallback for a fresh install (before the row exists) or when the
     * database is unreachable. Once a row exists, it always wins — and because
     * this runs on every boot, it also applies under `php artisan config:cache`.
     */
    private function bootRuntimeSettings(): void
    {
        try {
            $s = Setting::query()
                ->whereIn('key', [
                    'otp_enabled', 'notify_app_enabled', 'notify_mail_enabled',
                    'mailer', 'mail_host', 'mail_port', 'mail_username',
                    'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name',
                ])
                ->pluck('value', 'key');
        } catch (\Throwable $e) {
            return; // DB not migrated yet / unreachable — keep env-derived defaults.
        }

        $bool = static fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);

        // --- Feature flags ---
        if ($s->has('otp_enabled')) {
            config(['otp.enabled' => $bool($s->get('otp_enabled'))]);
        }

        if ($s->has('notify_app_enabled')) {
            config(['notifications.channels.database' => $bool($s->get('notify_app_enabled'))]);
        }

        if ($s->has('notify_mail_enabled')) {
            config(['notifications.channels.mail' => $bool($s->get('notify_mail_enabled'))]);
        }

        // --- Mail / SMTP ---
        $cfg = [];

        if ($s->get('mailer', '') !== '') {
            $cfg['mail.default'] = $s->get('mailer');
        }

        if ($s->get('mail_host', '') !== '') {
            $cfg['mail.mailers.smtp.host'] = $s->get('mail_host');
        }

        if ($s->get('mail_port', '') !== '') {
            $cfg['mail.mailers.smtp.port'] = (int) $s->get('mail_port');
        }

        if ($s->has('mail_username')) {
            $cfg['mail.mailers.smtp.username'] = $s->get('mail_username') ?: null;
        }

        if ($s->get('mail_from_address', '') !== '') {
            $cfg['mail.from.address'] = $s->get('mail_from_address');
        }

        if ($s->get('mail_from_name', '') !== '') {
            $cfg['mail.from.name'] = $s->get('mail_from_name');
        }

        if ($s->get('mail_encryption', '') !== '') {
            $cfg['mail.mailers.smtp.scheme'] = $s->get('mail_encryption') === 'ssl' ? 'smtps' : null;
        }

        if ($s->get('mail_password', '') !== '') {
            try {
                $cfg['mail.mailers.smtp.password'] = trim(Crypt::decryptString($s->get('mail_password')));
            } catch (\Throwable $e) {
                $cfg['mail.mailers.smtp.password'] = $s->get('mail_password'); // legacy/plaintext fallback
            }
        }

        if ($cfg) {
            config($cfg);
        }
    }
}
