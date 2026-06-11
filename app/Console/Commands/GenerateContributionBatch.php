<?php
namespace App\Console\Commands;

use App\Models\Auth\User;
use App\Models\Cpf\CpfContributionBatch;
use App\Services\ContributionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateContributionBatch extends Command
{
    protected $signature = 'cpf:generate-contribution-batch
                            {--month= : Target month as YYYY-MM (defaults to the current month)}';

    protected $description = 'Generate the monthly auto-draft CPF contribution batch for officer review.';

    public function handle(ContributionService $service): int
    {
        $target = $this->option('month')
            ? Carbon::parse($this->option('month') . '-01')
            : now()->startOfMonth();

        $exists = CpfContributionBatch::whereYear('contribution_month', $target->year)
            ->whereMonth('contribution_month', $target->month)
            ->exists();

        if ($exists) {
            $this->warn("A contribution batch for {$target->format('F Y')} already exists. Skipping.");
            return self::SUCCESS;
        }

        // No authenticated user during scheduled runs — attribute to a system admin.
        $systemUser = User::role('Admin')->first() ?? User::first();

        if (! $systemUser) {
            $this->error('No users found to attribute the auto-draft batch to. Aborting.');
            return self::FAILURE;
        }

        $batch = $service->generateBatch(
            month: $target->month,
            year: $target->year,
            createdBy: $systemUser->id,
        );

        $this->info("Draft batch #{$batch->id} generated for {$target->format('F Y')} ({$batch->employeeCount()} employees).");

        return self::SUCCESS;
    }
}
