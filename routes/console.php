<?php

use App\Console\Commands\AnnualIncrement;
use App\Console\Commands\GenerateContributionBatch;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateContributionBatch::class)
    ->monthlyOn(1, '01:00')
    ->timezone('Asia/Dhaka');

// Annual increment — 1 July, 01:00 (Asia/Dhaka).
Schedule::command(AnnualIncrement::class)
    ->yearlyOn(7, 1, '01:00')
    ->timezone('Asia/Dhaka');

// Run cleanup for old database backups at 00:30 (Spatie managed)
Schedule::command('backup:clean')->daily()->at('00:30');

// Run cleanup for old custom file backups at 00:45 (keep 7 days)
Schedule::command('backup:clean-files --days=7')
    ->daily()
    ->at('00:45')
    ->appendOutputTo(storage_path('logs/backup-files-cleanup.log'));

// Run daily database backup at 01:00
Schedule::command('backup:run --only-db')->daily()->at('12:53');
