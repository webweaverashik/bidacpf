<?php
namespace Database\Seeders;

use App\Models\Auth\User;
use App\Models\Interest\BankInterestBatch;
use App\Services\InterestDistributionService;
use App\Support\FiscalYearService;
use Illuminate\Database\Seeder;

/**
 * Seeds one fully-processed bank interest distribution.
 *
 * Mirrors the real workflow:
 *   1. Create a draft batch with a lump-sum interest amount.
 *   2. generate() — splits the lump sum across employees proportional to
 *      their current balance and records the per-employee distribution rows.
 *   3. submit() — posts a BANK_INTEREST credit to every employee's ledger.
 *
 * Runs last so the proportional split reflects balances AFTER opening
 * balances, contributions and advances have all been applied.
 *
 * Depends on: EmployeeSeeder, ContributionDemoSeeder, AdvanceDemoSeeder.
 */
class InterestDemoSeeder extends Seeder
{
    /**
     * Lump-sum interest the "bank" paid for the period (whole BDT).
     */
    private const TOTAL_INTEREST = 750_000;

    public function __construct(private InterestDistributionService $interestService)
    {}

    public function run(): void
    {
        $adminId = User::query()->value('id');

        if (! $adminId) {
            $this->command->warn('No users found — run UserSeeder first. Skipping InterestDemoSeeder.');
            return;
        }

        // Use the most recent 31 December as the cut-off / distribution date.
        $distributionDate = now()->month(12)->day(31);

        if ($distributionDate->isFuture()) {
            $distributionDate = $distributionDate->subYear();
        }

        // 1. Draft batch.
        $batch = BankInterestBatch::create([
            'distribution_date'      => $distributionDate,
            'fiscal_year'            => FiscalYearService::fromDate($distributionDate),
            'total_interest_amount'  => self::TOTAL_INTEREST,
            'total_eligible_balance' => 0, // filled by generate()
            'status'                 => \App\Enums\BatchStatus::DRAFT,
            'created_by'             => $adminId,
        ]);

        // 2. Generate proportional distributions.
        $this->interestService->generate($batch, $adminId);

        // 3. Submit — posts ledger credits to each employee.
        $this->interestService->submit($batch->fresh(), $adminId);

        $this->command->info('Bank interest batch generated and submitted (BDT ' . number_format(self::TOTAL_INTEREST) . ').');
    }
}
