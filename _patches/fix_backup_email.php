<?php

/**
 * Step 1 patch — BIDA CPF backup email fix.
 *
 * Run once from the project root:  php _patches/fix_backup_email.php
 *
 * Applies four targeted, idempotent edits:
 *   config/backup.php
 *     1. Disable Spatie's built-in "BackupWasSuccessful" mail notification
 *        (the controller already sends its own success email — this removes
 *        the duplicate AND stops the send to the dead placeholder address).
 *     2. Point the remaining Spatie notifications (failure / unhealthy /
 *        cleanup-failed) at a real, deliverable address instead of the
 *        hard-coded admin@example.com placeholder that triggers SMTP 550.
 *
 *   app/Http/Controllers/Setting/BackupController.php
 *     3. Fix the User import — App\Models\User does not exist; the real model
 *        is App\Models\Auth\User. The wrong import made notifyBackupManagers()
 *        throw "class not found", which was silently swallowed (no email).
 *     4. Fix the role name — Spatie role names are case-sensitive and the
 *        seeded role is "Admin", not "admin".
 */

$root = getcwd();

$edits = [
    "$root/config/backup.php" => [
        // (1) Disable the duplicate / placeholder-bound success notification.
        [
            'from' => "\\Spatie\\Backup\\Notifications\\Notifications\\BackupWasSuccessfulNotification::class     => ['mail'],",
            'to'   => "\\Spatie\\Backup\\Notifications\\Notifications\\BackupWasSuccessfulNotification::class     => [], // success email is sent by BackupController::notifyBackupManagers()",
        ],
        // (2) Replace the dead placeholder recipient with a real, env-driven one.
        [
            'from' => "'to'   => 'admin@example.com',",
            'to'   => "'to'   => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'info@ucms.uniquecoachingbd.com')),",
        ],
    ],

    "$root/app/Http/Controllers/Setting/BackupController.php" => [
        // (3) Correct the User model import.
        [
            'from' => "use App\\Models\\User;",
            'to'   => "use App\\Models\\Auth\\User;",
        ],
        // (4) Correct the (case-sensitive) role name.
        [
            'from' => "\$users = User::role('admin')->where('is_active', true)->get();",
            'to'   => "\$users = User::role('Admin')->where('is_active', true)->get();",
        ],
    ],
];

$applied = 0;
$skipped = 0;

foreach ($edits as $file => $changes) {
    if (! is_file($file)) {
        fwrite(STDERR, "  [skip] file not found: {$file}\n");
        continue;
    }

    $contents = file_get_contents($file);
    $original = $contents;

    foreach ($changes as $change) {
        if (str_contains($contents, $change['to'])) {
            echo "  [ok]   already applied in " . basename($file) . "\n";
            $skipped++;
            continue;
        }

        if (! str_contains($contents, $change['from'])) {
            fwrite(STDERR, "  [warn] target not found in " . basename($file) . ": {$change['from']}\n");
            continue;
        }

        $contents = str_replace($change['from'], $change['to'], $contents);
        echo "  [edit] " . basename($file) . ": applied\n";
        $applied++;
    }

    if ($contents !== $original) {
        file_put_contents($file, $contents);
    }
}

echo "\nDone. {$applied} edit(s) applied, {$skipped} already in place.\n";
echo "If you use config caching, run: php artisan config:clear\n";
