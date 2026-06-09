<?php

use Illuminate\Support\Facades\Artisan;

/**
 * Clears Laravel system-level caches (config, route, view, event).
 */
if (! function_exists('clearServerCache')) {
    function clearServerCache(): void
    {
        Artisan::call('optimize:clear');
    }
}
