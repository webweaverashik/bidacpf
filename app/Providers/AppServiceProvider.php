<?php
namespace App\Providers;

use App\Listeners\ScheduledTaskLogger;
use App\View\Composers\NotificationComposer;
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
    }
}
