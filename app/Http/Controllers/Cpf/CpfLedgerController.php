<?php
namespace App\Http\Controllers\Cpf;

use App\Http\Controllers\Controller;
use App\Models\Cpf\CpfLedger;
use App\Models\Employee\Employee;
use App\Services\LedgerService;
use Illuminate\View\View;

class CpfLedgerController extends Controller
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    public function index(): View
    {
        $employees = Employee::with('payScaleStep')
            ->search(request('search'))
            ->active()
            ->paginate(20)
            ->withQueryString();

        return view('cpf-ledger.index', compact('employees'));
    }

    public function transactions(): View
    {
        $transactions = CpfLedger::with('employee')
            ->when(request('employee_id'), fn($q) => $q->where('employee_id', request('employee_id')))
            ->when(request('type'), fn($q) => $q->where('transaction_type', request('type')))
            ->when(request('from'), fn($q) => $q->whereDate('transaction_date', '>=', request('from')))
            ->when(request('to'), fn($q) => $q->whereDate('transaction_date', '<=', request('to')))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        return view('cpf-ledger.transactions', compact('transactions', 'employees'));
    }

    public function show(Employee $employee): View
    {
        $ledgerEntries = $this->ledgerService->statement($employee->id);
        $balance       = $this->ledgerService->currentBalance($employee->id);

        return view('cpf-ledger.show', compact('employee', 'ledgerEntries', 'balance'));
    }
}
