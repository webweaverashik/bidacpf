<?php
namespace Database\Factories\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Auth\User;
use App\Models\Employee\Employee;
use App\Models\Employee\PayScaleStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CPF member employees.
 *
 * IMPORTANT: this factory expects the pay scale to already be seeded
 * (PayScale2015Seeder) because every employee must point at a real
 * PayScaleStep — salary figures are read from that step, never faked.
 * If no steps exist yet, pay_scale_step_id falls back to creating one,
 * but in practice the seeder guarantees steps are present first.
 *
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        // Pick a real, existing pay scale step at random so basic salary,
        // grade and step are always valid National Pay Scale 2015 values.
        $stepId = PayScaleStep::query()->inRandomOrder()->value('id');

        return [
            'cpf_account_no'    => 'PRA/K/' . fake()->unique()->numberBetween(1000, 9999) . '/' . fake()->numberBetween(10, 99),
            'name'              => fake()->name(),
            'designation'       => fake()->randomElement([
                'Investment Officer',
                'Personal Officer',
                'Field Officer',
                'Personal Assistant',
                'Investment Assistant',
                'Office Assistant',
                'Driver',
                'Messenger',
            ]),
            'email'             => fake()->optional()->safeEmail(),
            'mobile_number'     => '01' . fake()->numberBetween(3, 9) . fake()->numerify('########'),
            'photo'             => null,
            'joining_date'      => fake()->dateTimeBetween('-15 years', '-1 year')->format('Y-m-d'),
            'retirement_date'   => null,
            'pay_scale_step_id' => $stepId ?? PayScaleStep::factory(),
            'status'            => EmployeeStatus::ACTIVE,
            'is_active'         => true,
            // Created by the first user (admin) by default; overridable in seeder.
            'created_by'        => User::query()->value('id') ?? User::factory(),
        ];
    }

    /**
     * State: a retired employee (inactive, with a retirement date).
     */
    public function retired(): static
    {
        return $this->state(fn() => [
            'status'          => EmployeeStatus::RETIRED,
            'is_active'       => false,
            'retirement_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ]);
    }
}
