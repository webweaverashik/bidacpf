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
