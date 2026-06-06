<?php
namespace Database\Factories\Cpf;

use App\Enums\AdvanceStatus;
use App\Models\Auth\User;
use App\Models\Cpf\CpfAdvance;
use App\Models\Employee\Employee;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CPF advances (loans against accumulated balance).
 *
 * Default state is PENDING (no approval, no ledger impact). Use the
 * approved() / completed() states for richer scenarios. Note that creating
 * an APPROVED advance through this factory does NOT post a ledger
 * disbursement — only AdvanceService::approve() does that. The demo seeder
 * therefore approves advances through the service, not the factory state.
 *
 * @extends Factory<CpfAdvance>
 */
class CpfAdvanceFactory extends Factory
{
    protected $model = CpfAdvance::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(20_000, 300_000);

        return [
            'advance_no'         => 'ADV-' . fake()->unique()->numerify('######'),
            'employee_id'        => Employee::factory(),
            'application_date'   => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'approval_date'      => null,
            'approved_amount'    => $amount,
            'interest_rate'      => Setting::advanceInterestRate(), // default 5.00
            'installment_count'  => fake()->numberBetween(6, Setting::maxInstallments()),
            'outstanding_amount' => $amount,
            'status'             => AdvanceStatus::PENDING,
            'remarks'            => fake()->optional()->sentence(),
            'created_by'         => User::query()->value('id') ?? User::factory(),
            'approved_by'        => null,
        ];
    }

    /**
     * State: approved advance (metadata only — see class note on ledger).
     */
    public function approved(): static
    {
        return $this->state(fn() => [
            'status'        => AdvanceStatus::APPROVED,
            'approval_date' => now(),
            'approved_by'   => User::query()->value('id') ?? User::factory(),
        ]);
    }

    /**
     * State: fully recovered advance.
     */
    public function completed(): static
    {
        return $this->state(fn() => [
            'status'             => AdvanceStatus::COMPLETED,
            'approval_date'      => now()->subMonths(6),
            'approved_by'        => User::query()->value('id') ?? User::factory(),
            'outstanding_amount' => 0,
        ]);
    }
}
