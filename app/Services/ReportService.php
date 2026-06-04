<?php
namespace App\Services;

use App\Models\CpfLedger;
use App\Models\Employee;

class ReportService
{
    /**
     * Employee statement.
     */
    public function employeeStatement(Employee $employee)
    {
        return CpfLedger::query()->where('employee_id', $employee->id)->orderBy('transaction_date')->orderBy('id')->get();
    }

    /**
     * Current balance.
     */
    public function currentBalance(Employee $employee): int
    {
        return (int) $employee->ledgers()->latest('transaction_date')->latest('id')->value('balance');
    }

    /**
     * Outstanding advance.
     */
    public function outstandingAdvance(Employee $employee): int
    {
        return (int) $employee->advances()->approved()->sum('outstanding_amount');
    }
}
