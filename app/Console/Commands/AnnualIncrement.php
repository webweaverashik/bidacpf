<?php
namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Models\Auth\User;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScaleStep;
use App\Services\Employee\EmployeeSalaryService;
use Illuminate\Console\Command;

/**
 * Annual increment (BIDA — effective 1 July).
 *
 * Moves every eligible member to the NEXT step within their current grade /
 * pay scale. Mirrors the GenerateContributionBatch command:
 *   - runs without an authenticated user (scheduled), so changes are
 *     attributed to a system Admin via created_by;
 *   - is idempotent — it will not apply a second increment to a member who
 *     already received one in the same increment year (unless --force);
 *   - skips anyone already at the top step of their grade, anyone without a
 *     pay scale step, and anyone who is inactive or finally settled.
 *
 * The actual step change + salary-history row is written through the existing
 * EmployeeSalaryService::updateStep() so all the usual logging/auditing fires.
 */
class AnnualIncrement extends Command
{
    protected $signature = 'cpf:annual-increment
                            {--year= : Increment year (defaults to the current year)}
                            {--force : Re-apply even if an increment already exists for the year}';

    protected $description = 'Move every eligible active employee to the next pay-scale step (annual increment, 1 July).';

    public function handle(EmployeeSalaryService $service): int
    {
        $year  = (int) ($this->option('year') ?: now()->year);
        $force = (bool) $this->option('force');

        // No authenticated user during scheduled runs — attribute to a system Admin.
        $systemUser = User::role('Admin')->first() ?? User::first();

        if (! $systemUser) {
            $this->error('No users found to attribute the annual increment to. Aborting.');
            return self::FAILURE;
        }

        // Active members only; finally-settled members are inactive/non-active
        // status and are filtered out here (and guarded again per row).
        $employees = Employee::query()
            ->where('is_active', true)
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->with('payScaleStep')
            ->get();

        $applied        = 0;
        $atTopStep      = 0;
        $alreadyDone    = 0;
        $withoutScale   = 0;
        $settledSkipped = 0;

        foreach ($employees as $employee) {
            // Defensive: never increment a finally-settled member.
            if ($employee->isFinallySettled()) {
                $settledSkipped++;
                continue;
            }

            $current = $employee->payScaleStep;

            if (! $current) {
                $withoutScale++;
                continue;
            }

            // Idempotency guard — skip if already incremented this year.
            if (! $force) {
                $exists = EmployeeSalaryHistory::query()
                    ->where('employee_id', $employee->id)
                    ->where('change_type', 'annual_increment')
                    ->whereYear('effective_date', $year)
                    ->exists();

                if ($exists) {
                    $alreadyDone++;
                    continue;
                }
            }

            // Is there a next step in the same grade / pay scale?
            $nextStep = PayScaleStep::query()
                ->where('pay_scale_id', $current->pay_scale_id)
                ->where('grade', $current->grade)
                ->where('step', $current->step + 1)
                ->first();

            if (! $nextStep) {
                $atTopStep++;
                continue;
            }

            $service->updateStep(
                employee: $employee,
                step: $nextStep,
                changeType: 'annual_increment',
                createdBy: $systemUser->id,
                remarks: "Annual increment effective 01-Jul-{$year}.",
            );

            $applied++;
        }

        $this->info("Annual increment {$year}: {$applied} applied, {$atTopStep} at top step, "
            . "{$alreadyDone} already incremented, {$withoutScale} without a pay scale, {$settledSkipped} settled.");

        return self::SUCCESS;
    }
}
