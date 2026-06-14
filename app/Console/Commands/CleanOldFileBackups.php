<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldFileBackups extends Command
{
    protected $signature   = 'backup:clean-files {--days=7 : Number of days to keep file backups}';
    protected $description = 'Delete custom application file backups older than the specified number of days';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $disk      = 'local';
        $filesPath = config('backup.backup.name', 'Laravel') . '-files/';
        $threshold = Carbon::now()->subDays($days)->timestamp;

        $this->info("Cleaning file backups older than {$days} days from [{$filesPath}]...");

        if (! Storage::disk($disk)->exists($filesPath)) {
            $this->warn("Files backup directory does not exist: {$filesPath}");
            return self::SUCCESS;
        }

        $files   = Storage::disk($disk)->files($filesPath);
        $deleted = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if (! str_ends_with($file, '.zip')) {
                continue;
            }

            $lastModified = Storage::disk($disk)->lastModified($file);

            if ($lastModified < $threshold) {
                Storage::disk($disk)->delete($file);
                $this->line(" <fg=red>Deleted:</> " . basename($file) . ' (' . Carbon::createFromTimestamp($lastModified)->toDateTimeString() . ')');
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Deleted: {$deleted}, Kept: {$skipped}.");

        return self::SUCCESS;
    }
}
