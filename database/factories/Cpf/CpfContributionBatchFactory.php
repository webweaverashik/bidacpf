<?php
namespace Database\Factories\Cpf;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\Cpf\CpfContributionBatch;
use App\Support\FiscalYearService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for monthly CPF contribution batches.
 *
 * In normal operation batches are created by ContributionService (which
 * also populates the child contribution rows). This factory exists for
 * lightweight tests / table views where the child rows are not required.
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
            'status'             => BatchStatus::DRAFT,
            'submitted_at'       => null,
            'submitted_by'       => null,
            'remarks'            => null,
            'created_by'         => User::query()->value('id') ?? User::factory(),
        ];
    }

    /**
     * State: a submitted (posted) batch.
     */
    public function submitted(): static
    {
        return $this->state(fn() => [
            'status'       => BatchStatus::SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => User::query()->value('id') ?? User::factory(),
        ]);
    }
}
