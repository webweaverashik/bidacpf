<?php
namespace Database\Factories\Cpf;

use App\Models\Auth\User;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CPF opening balances (migration / onboarding snapshots).
 *
 * An opening balance captures what an employee had accumulated BEFORE the
 * system went live. net_balance is computed from the parts so the row is
 * always internally consistent:
 *
 *     net_balance = self + government + interest - outstanding_advance
 *
 * @extends Factory<CpfOpeningBalance>
 */
class CpfOpeningBalanceFactory extends Factory
{
    protected $model = CpfOpeningBalance::class;

    public function definition(): array
    {
        // Generate believable component amounts (whole BDT, integers only).
        $self        = fake()->numberBetween(50_000, 600_000);
        $government  = (int) round($self * 0.833); // govt tracks ~83.3% of self historically
        $interest    = fake()->numberBetween(5_000, 60_000);
        $outstanding = fake()->boolean(30) ? fake()->numberBetween(10_000, 150_000) : 0;

        return [
            'employee_id'             => Employee::factory(),
            // Opening balances are pre-system snapshots and MUST predate every seeded contribution. ContributionDemoSeeder posts batches up to 6 months back, so keep this comfortably earlier than that window (using the same now() anchor).
            'effective_date'          => now()->subMonths(18)->startOfMonth()->format('Y-m-d'),
            'self_contribution'       => $self,
            'government_contribution' => $government,
            'interest_amount'         => $interest,
            'outstanding_advance'     => $outstanding,
            // Keep net consistent with the components.
            'net_balance'             => $self + $government + $interest - $outstanding,
            'remarks'                 => 'Imported opening balance (demo data)',
            'created_by'              => User::query()->value('id') ?? User::factory(),
        ];
    }
}
