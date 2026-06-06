<?php
namespace Database\Factories\Interest;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\Interest\BankInterestBatch;
use App\Support\FiscalYearService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for bank interest distribution batches.
 *
 * Interest is distributed twice a year (30 June / 31 December per the
 * proposal). total_eligible_balance is left at 0 in the default state —
 * it is populated by InterestDistributionService::generate() once the
 * per-employee eligible balances are known.
 *
 * @extends Factory<BankInterestBatch>
 */
class BankInterestBatchFactory extends Factory
{
    protected $model = BankInterestBatch::class;

    public function definition(): array
    {
        // Half-yearly cut-off: either 31 Dec or 30 Jun.
        $distributionDate = fake()->randomElement([
            now()->month(6)->day(30),
            now()->month(12)->day(31),
        ]);

        return [
            'distribution_date'      => $distributionDate->format('Y-m-d'),
            'fiscal_year'            => FiscalYearService::fromDate($distributionDate),
            'total_interest_amount'  => fake()->numberBetween(200_000, 2_000_000),
            'total_eligible_balance' => 0, // set during generate()
            'status'                 => BatchStatus::DRAFT,
            'remarks'                => null,
            'created_by'             => User::query()->value('id') ?? User::factory(),
            'submitted_by'           => null,
            'submitted_at'           => null,
        ];
    }
}
