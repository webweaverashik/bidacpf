<?php
namespace App\Services;

use App\Models\Cpf\CpfLedger;
use App\Support\FiscalYearService;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /*
    |--------------------------------------------------------------------------
    | Responsibilities
    |--------------------------------------------------------------------------
    | - Create a ledger entry (with running balance auto-calculated)
    | - Get current balance for an employee
    | - Get opening balance for a fiscal year
    | - Provide a base query / full statement for an employee
    */

    /**
     * Create a ledger entry.
     *
     * The running balance is calculated by fetching the employee's latest
     * balance and applying the new credit/debit.  The whole operation runs
     * inside a transaction with a SELECT ... FOR UPDATE lock on the latest
     * ledger row so concurrent inserts for the same employee cannot produce
     * a race condition on the balance column.
     */
    public function create(array $data): CpfLedger
    {
        return DB::transaction(function () use ($data) {
            // Lock this employee's rows for the duration.
            CpfLedger::where('employee_id', $data['employee_id'])->lockForUpdate()->count();

            $entry = CpfLedger::create([ ...$data, 'balance' => 0]);

            $this->recalculateFrom($data['employee_id'], $data['transaction_date']);

            return $entry->refresh();
        });
    }

    /**
     * Recompute running balances from a given date forward, in (date, id) order.
     * In-order appends touch only the new row; a back-dated insert repairs every
     * later row so the column always equals the true cumulative net.
     */
    private function recalculateFrom(int $employeeId, $fromDate): void
    {
        $running = (int) CpfLedger::query()
            ->where('employee_id', $employeeId)
            ->where('transaction_date', '<', $fromDate)
            ->latest('transaction_date')->latest('id')
            ->value('balance');

        CpfLedger::query()
            ->where('employee_id', $employeeId)
            ->where('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date')->orderBy('id')
            ->each(function ($row) use (&$running) {
                $running += (int) $row->credit - (int) $row->debit;
                if ((int) $row->balance !== $running) {
                    $row->update(['balance' => $running]);
                }
            });
    }

    /**
     * Get the current (latest) balance for an employee.
     *
     * Returns 0 if no ledger entries exist yet (e.g. brand-new employee
     * whose opening balance has not been posted yet).
     */
    public function currentBalance(int $employeeId): int
    {
        return (int) CpfLedger::query()
            ->where('employee_id', $employeeId)
            ->latest('transaction_date')
            ->latest('id')
            ->value('balance');
    }

    /**
     * Get the opening balance of a fiscal year for an employee.
     *
     * "Opening balance" = the running balance on the last ledger entry
     * strictly before the fiscal year start date.  Returns 0 if there
     * are no entries before that date.
     */
    public function fiscalYearOpeningBalance(int $employeeId, string $fiscalYear): int
    {
        $startDate = \App\Support\FiscalYearService::startDate($fiscalYear);

        return (int) CpfLedger::query()
            ->where('employee_id', $employeeId)
            ->where('transaction_date', '<', $startDate)
            ->latest('transaction_date')
            ->latest('id')
            ->value('balance');
    }

    /**
     * Base ordered query for an employee's ledger.
     *
     * Ordered by transaction_date ASC then id ASC so entries on the same
     * date appear in insertion order (preserving the correct running balance
     * sequence).
     */
    public function ledgerQuery(int $employeeId)
    {
        return CpfLedger::query()
            ->where('employee_id', $employeeId)
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    /**
     * Full ledger statement for an employee (all entries, ordered).
     */
    public function statement(int $employeeId)
    {
        return $this->ledgerQuery($employeeId)->get();
    }

    /**
     * Ledger statement filtered to a fiscal year.
     *
     * Useful for annual account slips and fiscal-year reports.
     */
    public function statementForFiscalYear(int $employeeId, string $fiscalYear)
    {
        $startDate = FiscalYearService::startDate($fiscalYear);
        $endDate   = FiscalYearService::endDate($fiscalYear);

        return $this->ledgerQuery($employeeId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
    }
}
