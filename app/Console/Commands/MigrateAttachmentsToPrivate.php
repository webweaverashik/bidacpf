<?php
namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;

class MigrateAttachmentsToPrivate extends Command
{
    protected $signature   = 'attachments:migrate-private {--dry-run}';
    protected $description = 'Move attachment files from public/ into storage/app/private/ (run once).';

    public function handle(): int
    {
        $dry   = $this->option('dry-run');
        $moved = $missing = $skipped = 0;

        foreach (Attachment::cursor() as $a) {
            $rel = ltrim($a->file_path, '/');
            $src = public_path($rel);
            $dst = storage_path('app/private/' . $rel);

            if (is_file($dst)) {$skipped++;
                continue;} // already moved
            if (! is_file($src)) {$missing++;
                $this->warn("Missing: {$rel}");
                continue;}

            $this->line(($dry ? '[dry] ' : '') . "move {$rel}");
            if (! $dry) {
                $dir = dirname($dst);
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                rename($src, $dst);
            }
            $moved++;
        }

        $this->info("Done. moved={$moved} skipped={$skipped} missing={$missing}");

        return self::SUCCESS;
    }
}
