<?php
namespace Database\Factories\Cpf;

use App\Models\Cpf\CpfContribution;
use App\Models\Cpf\CpfContributionBatch;
use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Support\MoneyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for an individual employee's contribution row inside a batch.
 *
 * Employee and government amounts are derived from basic salary using the
 * configured rates and the system rounding policy, so figures match what
 * ContributionService would have produced.
 *
 * @extends Factory<CpfContribution>
 */
class CpfContributionFactory extends Factory
{
    protected $model = CpfContribution::class;

    public function definition(): array
    {
        $basicSalary = fake()->numberBetween(8_250, 78_000); // National Pay Scale 2015 range

                                                                 // Use the same rates + rounding the service uses for consistency.
        $employeeRate   = Setting::employeeContributionRate();   // default 10
        $governmentRate = Setting::governmentContributionRate(); // default 8.33

        return [
            'cpf_contribution_batch_id' => CpfContributionBatch::factory(),
            'employee_id'               => Employee::factory(),
            'basic_salary'              => $basicSalary,
            'employee_contribution'     => MoneyService::percentage($basicSalary, $employeeRate),
            'government_contribution'   => MoneyService::percentage($basicSalary, $governmentRate),
            'remarks'                   => null,
        ];
    }
}
