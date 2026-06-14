<?php

/**
 * Step 4 patch — wire up the Scheduled Task log.
 *
 * Run once from the project root:  php _patches/register_scheduled_tasks.php
 *
 * 1. Registers the ScheduledTaskLogger subscriber in AppServiceProvider::boot()
 *    so the scheduler events are captured.
 * 2. Adds a "Scheduled Tasks" link to the sidebar, right after "Audit Logs"
 *    inside the same Admin-only block of the Reports accordion.
 *
 * Both edits are idempotent. If a target isn't found (because the file was
 * customised), the script says so and the change can be made by hand using the
 * snippet it prints.
 */

$root = getcwd();
$applied = 0;

// ── 1. AppServiceProvider: register the subscriber ──────────────────────────
$providerPath = "$root/app/Providers/AppServiceProvider.php";
$register = '        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\ScheduledTaskLogger::class);';

if (is_file($providerPath)) {
    $src = file_get_contents($providerPath);

    if (str_contains($src, 'ScheduledTaskLogger::class')) {
        echo "  [ok]   subscriber already registered in AppServiceProvider\n";
    } elseif (preg_match('/public function boot\(\)\s*:\s*void\s*\{/', $src, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $src = substr($src, 0, $pos) . "\n" . $register . substr($src, $pos);
        file_put_contents($providerPath, $src);
        echo "  [edit] registered subscriber in AppServiceProvider::boot()\n";
        $applied++;
    } else {
        echo "  [warn] could not find boot(): void in AppServiceProvider. Add this line to boot() manually:\n";
        echo "         " . trim($register) . "\n";
    }
} else {
    echo "  [warn] app/Providers/AppServiceProvider.php not found. Register the subscriber in any provider's boot():\n";
    echo "         " . trim($register) . "\n";
}

// ── 2. Sidebar: add the "Scheduled Tasks" link after "Audit Logs" ───────────
$sidebarPath = "$root/resources/views/layouts/partials/sidebar.blade.php";

$from = <<<'EOT'
                                            <span class="menu-title">Audit Logs</span>
                                        </a>
                                    </div>
                                @endrole
EOT;

$to = <<<'EOT'
                                            <span class="menu-title">Audit Logs</span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('scheduled-tasks.*') ? 'active' : '' }}"
                                            href="{{ route('scheduled-tasks.index') }}" id="scheduled_tasks_link">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot"></span>
                                            </span>
                                            <span class="menu-title">Scheduled Tasks</span>
                                        </a>
                                    </div>
                                @endrole
EOT;

if (is_file($sidebarPath)) {
    $src = file_get_contents($sidebarPath);

    if (str_contains($src, "scheduled-tasks.index")) {
        echo "  [ok]   sidebar link already present\n";
    } elseif (str_contains($src, $from)) {
        file_put_contents($sidebarPath, str_replace($from, $to, $src));
        echo "  [edit] added Scheduled Tasks link to the sidebar\n";
        $applied++;
    } else {
        echo "  [warn] could not locate the Audit Logs sidebar block. Add a menu-item linking to route('scheduled-tasks.index') by hand.\n";
    }
} else {
    echo "  [warn] sidebar partial not found at {$sidebarPath}\n";
}

echo "\nDone. {$applied} edit(s) applied.\n";
echo "Then run: php artisan migrate  &&  php artisan route:clear\n";
