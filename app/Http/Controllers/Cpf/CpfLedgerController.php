<?php
namespace App\Http\Controllers\Cpf;

use App\Exports\Employee\EmployeeLedgerExport;
use App\Exports\LedgerTransactionsExport;
use App\Exports\Employee\MembersLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\Cpf\CpfLedger;
use App\Models\Employee\Employee;
use App\Services\Cpf\LedgerService;
use App\Support\FiscalYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CpfLedgerController extends Controller
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /*
    |--------------------------------------------------------------------------
    | Pages (shells — data arrives via the *Data feeds in Step 5b)
    |--------------------------------------------------------------------------
    */
    public function index(): View
    {
        return view('cpf-ledger.index');
    }

    public function transactions(): View
    {
        $employees = Employee::orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        return view('cpf-ledger.transactions', compact('employees'));
    }

    public function show(Employee $employee): View
    {
        $employee->load('payScaleStep.payScale');

        $balance      = $this->ledgerService->currentBalance($employee->id);
        $totalCredits = (int) $employee->ledgers()->sum('credit');
        $totalDebits  = (int) $employee->ledgers()->sum('debit');

        // Distinct fiscal years present in this employee's ledger (for the filter).
        $fiscalYears = $employee->ledgers()
            ->orderBy('transaction_date')
            ->pluck('transaction_date')
            ->map(fn($d) => FiscalYearService::fromDate($d))
            ->unique()->values();

        return view('cpf-ledger.show', compact('employee', 'balance', 'totalCredits', 'totalDebits', 'fiscalYears'));
    }

    /*
    |--------------------------------------------------------------------------
    | Members — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function indexData(Request $request): JsonResponse
    {
        $query = $this->membersQuery($request);

        $recordsTotal    = Employee::count();
        $recordsFiltered = (clone $query)->count();

        // Ordering
        $orderMap = [
            1 => 'employees.cpf_account_no',
            2 => 'employees.name',
            3 => 'employees.designation',
            4 => 'pay_scales.name',
            5 => 'pay_scale_steps.grade',
            6 => 'pay_scale_steps.basic_salary',
            7 => 'current_balance',
            8 => 'employees.is_active',
        ];
        $colIdx = (int) $request->input('order.0.column', 2);
        $dir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderMap[$colIdx] ?? 'employees.name', $dir);

        // Pagination
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($e) use (&$i) {
            $i++;
            $url = route('cpf-ledger.show', $e->id);

            return [
                'DT_RowIndex'  => $i,
                'account'      => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($e->cpf_account_no) . '</a>',
                'name'         => e($e->name),
                'designation'  => e($e->designation),
                'pay_scale'    => e($e->ps_name ?? '—'),
                'grade'        => $e->ps_grade ?? '—',
                'basic_salary' => number_format((int) $e->ps_basic),
                'balance'      => number_format((int) $e->current_balance),
                'status'       => $e->is_active
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>',
                'actions'      => '<a href="' . $url . '" title="View Statement" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-eye fs-2"></i></a>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function export(Request $request)
    {
        $employees = $this->membersQuery($request)->orderBy('employees.name')->get();
        $filename  = 'cpf-members-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new MembersLedgerExport($employees), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-ledger.members-pdf', ['employees' => $employees, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new MembersLedgerExport($employees), "$filename.xlsx"),
        };
    }

    private function membersQuery(Request $request)
    {
        $query = Employee::query()
            ->leftJoin('pay_scale_steps', 'employees.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->leftJoin('pay_scales', 'pay_scale_steps.pay_scale_id', '=', 'pay_scales.id')
            ->select([
                'employees.id',
                'employees.cpf_account_no',
                'employees.name',
                'employees.designation',
                'employees.is_active',
                'pay_scale_steps.grade as ps_grade',
                'pay_scale_steps.basic_salary as ps_basic',
                'pay_scales.name as ps_name',
            ])
            ->selectSub($this->balanceSubQuery(), 'current_balance');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.designation', 'like', "%{$search}%")
                    ->orWhere('pay_scales.name', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('employees.is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('employees.is_active', false);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Transactions — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function transactionsData(Request $request): JsonResponse
    {
        $query = $this->transactionsQuery($request);

        $recordsTotal    = CpfLedger::count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'cpf_ledgers.transaction_date',
            2 => 'employees.name',
            3 => 'cpf_ledgers.transaction_type',
            4 => 'cpf_ledgers.reference_no',
            5 => 'cpf_ledgers.debit',
            6 => 'cpf_ledgers.credit',
            7 => 'cpf_ledgers.balance',
        ];
        $colIdx = (int) $request->input('order.0.column', 1);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'cpf_ledgers.transaction_date', $dir)
            ->orderBy('cpf_ledgers.id', 'desc');

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($t) use (&$i) {
            $i++;
            $url = route('cpf-ledger.show', $t->employee_id);

            return [
                'DT_RowIndex' => $i,
                'date'        => $t->transaction_date->format('d M Y'),
                'employee'    => '<a href="' . $url . '" class="text-gray-800 text-hover-primary">' . e($t->emp_name)
                . '<span class="text-muted fs-8 d-block">' . e($t->emp_acc) . '</span></a>',
                'type'        => $t->transaction_type->label(),
                'reference'   => e($t->reference_no),
                'debit'       => $t->debit > 0 ? '<span class="text-danger">' . number_format($t->debit) . '</span>' : '—',
                'credit'      => $t->credit > 0 ? '<span class="text-success">' . number_format($t->credit) . '</span>' : '—',
                'balance'     => '<span class="fw-bold">' . number_format($t->balance) . '</span>',
                'remarks'     => '<span class="text-muted">' . e($t->remarks) . '</span>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function transactionsExport(Request $request)
    {
        $rows = $this->transactionsQuery($request)
            ->orderBy('cpf_ledgers.transaction_date', 'desc')->orderBy('cpf_ledgers.id', 'desc')->get();
        $filename = 'cpf-ledger-transactions-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new LedgerTransactionsExport($rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-ledger.transactions-pdf', ['rows' => $rows, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new LedgerTransactionsExport($rows), "$filename.xlsx"),
        };
    }

    private function transactionsQuery(Request $request)
    {
        $query = CpfLedger::query()
            ->join('employees', 'cpf_ledgers.employee_id', '=', 'employees.id')
            ->select('cpf_ledgers.*', 'employees.name as emp_name', 'employees.cpf_account_no as emp_acc');

        if ($employeeId = $request->input('employee_id')) {
            $query->where('cpf_ledgers.employee_id', $employeeId);
        }
        if ($type = $request->input('type')) {
            $query->where('cpf_ledgers.transaction_type', $type);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('cpf_ledgers.transaction_date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('cpf_ledgers.transaction_date', '<=', $to);
        }
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('cpf_ledgers.reference_no', 'like', "%{$search}%")
                    ->orWhere('cpf_ledgers.remarks', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Statement (single employee) — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function statementData(Employee $employee, Request $request): JsonResponse
    {
        $query = $this->statementQuery($employee, $request);

        $recordsTotal    = $employee->ledgers()->count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'transaction_date',
            2 => 'transaction_type',
            3 => 'reference_no',
            4 => 'remarks',
            5 => 'debit',
            6 => 'credit',
            7 => 'balance',
        ];
        $colIdx = (int) $request->input('order.0.column', 1);
        $dir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderMap[$colIdx] ?? 'transaction_date', $dir)->orderBy('id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($e) use (&$i) {
            $i++;

            return [
                'DT_RowIndex' => $i,
                'date'        => $e->transaction_date->format('d M Y'),
                'type'        => $e->transaction_type->label(),
                'reference'   => e($e->reference_no),
                'remarks'     => '<span class="text-muted">' . e($e->remarks) . '</span>',
                'debit'       => $e->debit > 0 ? '<span class="text-danger">' . number_format($e->debit) . '</span>' : '—',
                'credit'      => $e->credit > 0 ? '<span class="text-success">' . number_format($e->credit) . '</span>' : '—',
                'balance'     => '<span class="fw-bold">' . number_format($e->balance) . '</span>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function statementExport(Employee $employee, Request $request)
    {
        $employee->load('payScaleStep.payScale');

        $entries = $this->statementQuery($employee, $request)
            ->with('creator')
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $balance  = $this->ledgerService->currentBalance($employee->id);
        $acc      = str_replace(['/', '\\', ' '], '-', $employee->cpf_account_no);
        $filename = 'cpf-statement-' . $acc . '-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new EmployeeLedgerExport($entries, $employee), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-ledger.statement-pdf', [
                'employee'    => $employee,
                'entries'     => $entries,
                'balance'     => $balance,
                'generatedAt' => now(),
            ])->setPaper('a4', 'portrait')->download("$filename.pdf"),
            default => Excel::download(new EmployeeLedgerExport($entries, $employee), "$filename.xlsx"),
        };
    }

    private function statementQuery(Employee $employee, Request $request)
    {
        $query = CpfLedger::query()->where('employee_id', $employee->id);

        if ($fy = $request->input('fiscal_year')) {
            $query->whereBetween('transaction_date', [
                FiscalYearService::startDate($fy),
                FiscalYearService::endDate($fy),
            ]);
        }
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhere('transaction_type', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function balanceSubQuery(): \Closure
    {
        return function ($q) {
            $q->from('cpf_ledgers')
                ->whereColumn('cpf_ledgers.employee_id', 'employees.id')
                ->orderByDesc('transaction_date')->orderByDesc('id')
                ->limit(1)
                ->select('balance');
        };
    }
}
