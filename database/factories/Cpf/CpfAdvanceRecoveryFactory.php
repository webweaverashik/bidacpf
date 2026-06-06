<?php
namespace Database\Factories\Cpf;

use App\Models\Auth\User;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for advance recovery (installment) rows.
 *
 * As with advances, recoveries created directly via this factory do NOT
 * post ledger credits or decrement the parent advance — that logic lives
 * in AdvanceService::recovery(). Use the factory only when you need
 * standalone recovery rows; use the service for balance-consistent demo
 * data.
 *
 * @extends Factory<CpfAdvanceRecovery>
 */
class CpfAdvanceRecoveryFactory extends Factory
{
    protected $model = CpfAdvanceRecovery::class;

    public function definition(): array
    {
        return [
            'cpf_advance_id' => CpfAdvance::factory(),
            'recovery_date'  => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'amount'         => fake()->numberBetween(2_000, 25_000),
            'remarks'        => fake()->optional()->sentence(),
            'created_by'     => User::query()->value('id') ?? User::factory(),
        ];
    }
}
