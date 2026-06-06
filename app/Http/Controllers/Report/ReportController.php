<?php
namespace App\Http\Controllers\Report;

use App\Models\Employee;
use App\Services\LedgerService;
use App\Services\ReportService;
use App\Support\FiscalYearService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected LedgerService $ledgerService,
    ) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function accountSlip(): View
    {
        $employee   = null;
        $ledgers    = collect();
        $fiscalYear = request('fiscal_year', FiscalYearService::current());
        $employees  = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        if (request('employee_id')) {
            $employee = Employee::findOrFail(request('employee_id'));
            $ledgers  = $this->ledgerService->statementForFiscalYear($employee->id, $fiscalYear);
        }

        return view('reports.account-slip', compact('employee', 'ledgers', 'fiscalYear', 'employees'));
    }

    public function ledgerStatement(): View
    {
        $employee  = null;
        $ledgers   = collect();
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        if (request('employee_id')) {
            $employee = Employee::findOrFail(request('employee_id'));
            $ledgers  = $this->ledgerService->statement($employee->id);
        }

        return view('reports.ledger-statement', compact('employee', 'ledgers', 'employees'));
    }

    public function contributionSummary(): View
    {
        $fiscalYear = request('fiscal_year', FiscalYearService::current());

        return view('reports.contribution-summary', compact('fiscalYear'));
    }

    public function advanceSummary(): View
    {
        return view('reports.advance-summary');
    }

    // Export methods — implement with maatwebsite/excel
    public function exportAccountSlip()
    {
        // TODO: return Excel::download(new AccountSlipExport(...), 'account-slip.xlsx');
        abort(501, 'Export not yet implemented.');
    }

    public function exportLedgerStatement()
    {
        // TODO: return Excel::download(new LedgerStatementExport(...), 'ledger-statement.xlsx');
        abort(501, 'Export not yet implemented.');
    }

    public function exportContributionSummary()
    {
        // TODO: return Excel::download(new ContributionSummaryExport(...), 'contribution-summary.xlsx');
        abort(501, 'Export not yet implemented.');
    }

    public function exportAdvanceSummary()
    {
        // TODO: return Excel::download(new AdvanceSummaryExport(...), 'advance-summary.xlsx');
        abort(501, 'Export not yet implemented.');
    }
}
