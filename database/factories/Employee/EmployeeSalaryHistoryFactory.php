<?php
namespace Database\Factories\Employee;

use App\Models\Auth\User;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScaleStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for employee salary history rows.
 *
 * Each row records a point-in-time pay scale step assignment (initial
 * placement, annual increment, promotion, or revision).
 *
 * @extends Factory<EmployeeSalaryHistory>
 */
class EmployeeSalaryHistoryFactory extends Factory
{
    protected $model = EmployeeSalaryHistory::class;

    public function definition(): array
    {
        return [
            'employee_id'       => Employee::factory(),
            'pay_scale_step_id' => PayScaleStep::query()->inRandomOrder()->value('id') ?? PayScaleStep::factory(),
            'effective_date'    => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'change_type'       => fake()->randomElement(['initial', 'annual_increment', 'promotion', 'revision']),
            'remarks'           => fake()->optional()->sentence(),
            'created_by'        => User::query()->value('id') ?? User::factory(),
        ];
    }

    /**
     * State: the very first (initial) placement record.
     */
    public function initial(): static
    {
        return $this->state(fn() => ['change_type' => 'initial']);
    }
}
