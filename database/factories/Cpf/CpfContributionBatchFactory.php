<?php
namespace Database\Factories\Cpf;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\Cpf\CpfContributionBatch;
use App\Models\Setting;
use App\Support\FiscalYearService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for monthly CPF contribution batches.
 *
 * In normal operation batches are created by ContributionService (which
 * also populates the child contribution rows and snapshots the contribution
 * rates). This factory exists for lightweight tests / table views where the
 * child rows are not required.
 *
 * @extends Factory<CpfContributionBatch>
 */
class CpfContributionBatchFactory extends Factory
{
    protected $model = CpfContributionBatch::class;

    public function definition(): array
    {
        // First day of a random recent month.
        $month = now()->subMonths(fake()->numberBetween(0, 11))->startOfMonth();

        return [
            'contribution_month' => $month->format('Y-m-d'),
            'fiscal_year'        => FiscalYearService::fromDate($month),
            // Snapshot the rates the same way the service does.
            'employee_rate'      => Setting::employeeContributionRate(),
            'government_rate'    => Setting::governmentContributionRate(),
            'status'             => BatchStatus::DRAFT,
            'remarks'            => null,
            'submitted_by'       => null,
            'submitted_at'       => null,
            'approved_by'        => null,
            'approved_at'        => null,
            'reversed_by'        => null,
            'reversed_at'        => null,
            'created_by'         => User::query()->value('id') ?? User::factory(),
        ];
    }

    /**
     * State: submitted for approval — locked, awaiting admin, no ledger yet.
     */
    public function submitted(): static
    {
        return $this->state(fn() => [
            'status'       => BatchStatus::SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => User::query()->value('id') ?? User::factory(),
        ]);
    }

    /**
     * State: approved — in real operation this is the point the ledger is
     * posted. Implies the batch was submitted first.
     */
    public function approved(): static
    {
        return $this->state(function () {
            $userId = User::query()->value('id') ?? User::factory()->create()->id;

            return [
                'status'       => BatchStatus::APPROVED,
                'submitted_at' => now()->subDay(),
                'submitted_by' => $userId,
                'approved_at'  => now(),
                'approved_by'  => $userId,
            ];
        });
    }

    /**
     * State: reversed — implies it was submitted and approved first.
     */
    public function reversed(): static
    {
        return $this->state(function () {
            $userId = User::query()->value('id') ?? User::factory()->create()->id;

            return [
                'status'       => BatchStatus::REVERSED,
                'submitted_at' => now()->subDays(2),
                'submitted_by' => $userId,
                'approved_at'  => now()->subDay(),
                'approved_by'  => $userId,
                'reversed_at'  => now(),
                'reversed_by'  => $userId,
            ];
        });
    }
}
