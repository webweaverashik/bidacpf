<?php

use App\Console\Commands\GenerateContributionBatch;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateContributionBatch::class)
    ->monthlyOn(1, '01:00')
    ->timezone('Asia/Dhaka');
