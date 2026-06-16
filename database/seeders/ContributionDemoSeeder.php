<?php
namespace Database\Seeders;

use App\Models\Auth\User;
use App\Services\Cpf\ContributionService;
use Illuminate\Database\Seeder;

/**
 * Seeds several months of fully-approved CPF contribution batches.
 *
 * Uses ContributionService end-to-end (generate → submit → approve) so that:
 *   - one batch is created per month,
 *   - a contribution row is generated for every active employee,
 *   - on approval, employee + government ledger credits are posted with
 *     correct running balances.
 *
 * This is the realistic path — we never insert ledger rows directly.
 *
 * Depends on: EmployeeSeeder (active employees must exist).
 */
class ContributionDemoSeeder extends Seeder
{
    /**
     * How many trailing months of contributions to generate.
     */
    private const MONTHS_TO_SEED = 6;

    public function __construct(private ContributionService $contributionService)
    {}

    public function run(): void
    {
        $adminId = User::query()->value('id');

        if (! $adminId) {
            $this->command->warn('No users found — run UserSeeder first. Skipping ContributionDemoSeeder.');
            return;
        }

        // Walk backwards from last month so the newest batch is the most recent.
        for ($i = self::MONTHS_TO_SEED; $i >= 1; $i--) {
            $date = now()->subMonths($i);

            // 1. Generate the draft batch + per-employee contribution rows.
            $batch = $this->contributionService->generateBatch(
                month: (int) $date->month,
                year: (int) $date->year,
                createdBy: $adminId,
            );

            // 2. Submit for approval (locks the batch; no ledger yet).
            $this->contributionService->submitBatch($batch, $adminId);

            // 3. Approve — posts the employee + government ledger credits and
            //    finalises the running balances.
            $this->contributionService->approveBatch($batch, $adminId);
        }

        $this->command->info(self::MONTHS_TO_SEED . ' monthly contribution batches generated, submitted and approved.');
    }
}
