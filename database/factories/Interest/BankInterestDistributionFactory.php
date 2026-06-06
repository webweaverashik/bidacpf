<?php
namespace Database\Factories\Interest;

use App\Models\Employee\Employee;
use App\Models\Interest\BankInterestBatch;
use App\Models\Interest\BankInterestDistribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for a single employee's slice of a bank interest batch.
 *
 * The calculation_snapshot mirrors the structure written by
 * InterestDistributionService so report views and audits see a consistent
 * shape whether the row came from the service or this factory.
 *
 * @extends Factory<BankInterestDistribution>
 */
class BankInterestDistributionFactory extends Factory
{
    protected $model = BankInterestDistribution::class;

    public function definition(): array
    {
        $eligibleBalance = fake()->numberBetween(50_000, 700_000);
        $interest        = (int) round($eligibleBalance * fake()->randomFloat(4, 0.005, 0.02));

        return [
            'bank_interest_batch_id' => BankInterestBatch::factory(),
            'employee_id'            => Employee::factory(),
            'eligible_balance'       => $eligibleBalance,
            'interest_amount'        => $interest,
            'calculation_snapshot'   => [
                'eligible_balance' => $eligibleBalance,
                'posted_interest'  => $interest,
                'rounding_policy'  => 'HALF_UP',
            ],
        ];
    }
}
