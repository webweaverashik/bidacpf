<?php
namespace App\Services;

use App\Models\CpfLedger;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /*
      Responsibilities:
      - Create Ledger Entry
      - Calculate Balance
      - Get Current Balance
      - Get Opening Balance
      - Get Fiscal Year Balance
      */

    /**
     * Create ledger entry.
     */
    public function create(array $data): CpfLedger
    {
        return DB::transaction(function () use ($data) {
            $lastBalance = $this->currentBalance($data['employee_id']);

            $balance = $lastBalance + $data['credit'] - $data['debit'];

            return CpfLedger::create([ ...$data, 'balance' => $balance]);
        });
    }

    /**
     * Current balance.
     */
    public function currentBalance(int $employeeId): int
    {
        return (int) CpfLedger::query()->where('employee_id', $employeeId)->latest('transaction_date')->latest('id')->value('balance');
    }

    /**
     * Opening balance of fiscal year.
     */
    public function fiscalYearOpeningBalance(int $employeeId, string $fiscalYear): int
    {
        $startDate = \App\Support\FiscalYearService::startDate($fiscalYear);

        return (int) CpfLedger::query()->where('employee_id', $employeeId)->where('transaction_date', '<', $startDate)->latest('transaction_date')->latest('id')->value('balance');
    }

    /**
     * Employee ledger query.
     */
    public function ledgerQuery(int $employeeId)
    {
        return CpfLedger::query()->where('employee_id', $employeeId)->orderBy('transaction_date')->orderBy('id');
    }

    /**
     * Employee statement.
     */
    public function statement(int $employeeId)
    {
        return $this->ledgerQuery($employeeId)->get();
    }
}
