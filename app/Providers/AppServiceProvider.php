<?php
namespace App\Providers;

use App\Listeners\ScheduledTaskLogger;
use App\Models\Setting;
use App\View\Composers\NotificationComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
            $flags = Setting::query()
                ->whereIn('key', ['otp_enabled', 'notify_app_enabled', 'notify_mail_enabled'])
                ->pluck('value', 'key');
        } catch (Throwable $e) {
            // DB not migrated yet / unreachable — keep the env-derived defaults.
            return;
        }

        $bool = static fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);

        if ($flags->has('otp_enabled')) {
            config(['otp.enabled' => $bool($flags->get('otp_enabled'))]);
        }

        if ($flags->has('notify_app_enabled')) {
            config(['notifications.channels.database' => $bool($flags->get('notify_app_enabled'))]);
        }

        if ($flags->has('notify_mail_enabled')) {
            config(['notifications.channels.mail' => $bool($flags->get('notify_mail_enabled'))]);
        }
    }
}
