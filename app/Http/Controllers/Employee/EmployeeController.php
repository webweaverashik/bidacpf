<?php
namespace App\Http\Controllers\Employee;

use App\Enums\LedgerTransactionType;
use App\Enums\SettlementStatus;
use App\Enums\SourceType;
use App\Exports\EmployeeLedgerExport;
use App\Exports\EmployeesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Auth\User;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScale;
use App\Models\Employee\PayScaleStep;
use App\Services\LedgerService;
use App\Support\FiscalYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    /** In-request cache for foreign-key → label lookups in the activity log. */
    private array $refCache = [];

    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Employee listing — shell only. Rows arrive via the server-side
     * DataTable feed (data()); exports via export().
     */
    public function index(): View
    {
        return view('employees.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Server-side DataTable feed + filter-aware exports
    |--------------------------------------------------------------------------
    */

    /**
     * DataTables (server-side) endpoint for the employee list.
     */
    public function data(Request $request): JsonResponse
    {
        $canEdit           = (bool) $request->user()?->can('employee.update');
        $canViewSettlement = (bool) $request->user()?->can('cpf_settlement.view');

        $query           = $this->employeesQuery($request);
        $recordsTotal    = Employee::count();
        $recordsFiltered = (clone $query)->count();

        // Ordering (column index → DB column / alias).
        $orderMap = [
            1 => 'employees.cpf_account_no',
            2 => 'employees.name',
            3 => 'employees.designation',
            4 => 'employees.mobile_number',
            5 => 'employees.joining_date',
            6 => 'pay_scale_steps.grade',
            7 => 'pay_scale_steps.basic_salary',
            8 => 'current_balance',
            9 => 'employees.is_active',
        ];
        $colIdx = (int) $request->input('order.0.column', 2);
        $dir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderMap[$colIdx] ?? 'employees.name', $dir);

        // Pagination.
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($e) use (&$i, $canEdit, $canViewSettlement) {
            $i++;
            $showUrl = route('employees.show', $e->id);
            $editUrl = route('employees.edit', $e->id);
            $settled = (bool) $e->is_settled;

            if ($settled) {
                $status = '<span class="badge badge-light-info">Settled</span>';
                if ($canViewSettlement && $e->settlement_id) {
                    $status = '<a href="' . route('cpf-settlements.show', $e->settlement_id) . '" target="_blank" '
                        . 'class="text-hover-primary" title="View settlement">' . $status . '</a>';
                }
            } elseif ($e->is_active) {
                $status = '<span class="badge badge-light-success">Active</span>';
            } else {
                $status = '<span class="badge badge-light-danger">Inactive</span>';
            }

            $actions = '<a href="' . $showUrl . '" target="_blank" title="View Employee" '
                . 'class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-eye fs-2"></i></a>';

            if ($canEdit && ! $settled) {
                $actions .= '<a href="' . $editUrl . '" title="Edit Employee" '
                    . 'class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-pencil fs-2"></i></a>';
            } elseif ($canEdit && $settled) {
                $actions .= '<span class="btn btn-icon w-30px h-30px text-muted disabled" '
                    . 'title="Finally settled — locked"><i class="ki-outline ki-lock-2 fs-2"></i></span>';
            }

            return [
                'DT_RowIndex'  => $i,
                'account'      => '<a href="' . $showUrl . '" target="_blank" '
                . 'class="text-gray-800 text-hover-primary fw-bold">' . e($e->cpf_account_no) . '</a>',
                'name'         => e($e->name),
                'designation'  => e($e->designation),
                'mobile'       => e($e->mobile_number ?: '—'),
                'joining_date' => $e->joining_date ? $e->joining_date->format('d M Y') : '—',
                'grade'        => $e->ps_grade ?? '—',
                'basic_salary' => $e->ps_basic !== null ? number_format((int) $e->ps_basic) : '—',
                'balance'      => number_format((int) $e->current_balance),
                'status'       => $status,
                'actions'      => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Filter-aware export of the (full, unpaginated) filtered employee list.
     */
    public function export(Request $request): BinaryFileResponse | Response
    {
        $employees = $this->employeesQuery($request)->orderBy('employees.name')->get();
        $filename  = 'employees-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new EmployeesExport($employees), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.employees.index-pdf', [
                'employees'   => $employees,
                'filters'     => $this->indexFilters($request),
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new EmployeesExport($employees), "$filename.xlsx"),
        };
    }

    /**
     * Shared, filtered base query for the list feed and the exports.
     * Attaches current_balance + is_settled via correlated subqueries.
     */
    private function employeesQuery(Request $request)
    {
        $query = Employee::query()
            ->leftJoin('pay_scale_steps', 'employees.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->leftJoin('pay_scales', 'pay_scale_steps.pay_scale_id', '=', 'pay_scales.id')
            ->select([
                'employees.id',
                'employees.cpf_account_no',
                'employees.name',
                'employees.designation',
                'employees.mobile_number',
                'employees.email',
                'employees.joining_date',
                'employees.is_active',
                'employees.status',
                'pay_scale_steps.grade as ps_grade',
                'pay_scale_steps.step as ps_step',
                'pay_scale_steps.basic_salary as ps_basic',
                'pay_scales.name as ps_name',
            ])
            ->selectSub($this->balanceSubQuery(), 'current_balance')
            ->addSelect(['is_settled' => $this->settledSubQuery()])
            ->addSelect(['settlement_id' => function ($q) {
                $q->selectRaw('id')
                    ->from('cpf_final_settlements')
                    ->whereColumn('cpf_final_settlements.employee_id', 'employees.id')
                    ->where('cpf_final_settlements.status', SettlementStatus::APPROVED->value)
                    ->whereNull('cpf_final_settlements.deleted_at')
                    ->latest('id')
                    ->limit(1);
            }]);

        // Global search (DataTables sends search.value; exports send ?search=).
        $search = $request->input('search.value', $request->input('search'));
        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.designation', 'like', "%{$search}%")
                    ->orWhere('employees.mobile_number', 'like', "%{$search}%")
                    ->orWhere('employees.email', 'like', "%{$search}%")
                    ->orWhere('pay_scales.name', 'like', "%{$search}%");
            });
        }

        // Grade filter — accepts "8" or legacy "grade_8".
        $grade = $request->input('grade');
        if ($grade !== null && $grade !== '') {
            $grade = is_numeric($grade) ? (int) $grade : (int) str_replace('grade_', '', (string) $grade);
            $query->where('pay_scale_steps.grade', $grade);
        }

        // Activation filter (is_active).
        $active = $request->input('active_status');
        if ($active === 'active') {
            $query->where('employees.is_active', true);
        } elseif ($active === 'inactive') {
            $query->where('employees.is_active', false);
        }

        // Service status filter.
        $service = $request->input('service_status');
        if (in_array($service, ['active', 'retired', 'resigned', 'deceased'], true)) {
            $query->where('employees.status', $service);
        }

        return $query;
    }

    /** Normalised filter values for the PDF chip bar. */
    private function indexFilters(Request $request): array
    {
        return [
            'search'         => trim((string) $request->input('search.value', $request->input('search', ''))),
            'grade'          => $request->input('grade'),
            'active_status'  => $request->input('active_status'),
            'service_status' => $request->input('service_status'),
        ];
    }

    /** Correlated subquery → latest running ledger balance per employee. */
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

    /** Correlated subquery → 1 when the member has an APPROVED final settlement. */
    private function settledSubQuery(): \Closure
    {
        return function ($q) {
            $q->selectRaw('1')
                ->from('cpf_final_settlements')
                ->whereColumn('cpf_final_settlements.employee_id', 'employees.id')
                ->where('cpf_final_settlements.status', SettlementStatus::APPROVED->value)
                ->whereNull('cpf_final_settlements.deleted_at')
                ->limit(1);
        };
    }

    public function create(): View
    {
        // Load ALL active pay scales.
        // When only one exists the blade hides the selector (legacy behaviour).
        // When multiple exist the blade shows a pay scale <select>.
        $payScales = PayScale::active()
            ->orderByDesc('effective_year')
            ->get(['id', 'name', 'effective_year', 'is_active']);

        // Default / pre-selected pay scale (most recent active one).
        $defaultPayScale = $payScales->first();

        // Grades for the default scale — pre-populate the grade dropdown.
        $grades = $defaultPayScale
            ? $defaultPayScale->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            : collect();

        return view('employees.create', compact('payScales', 'defaultPayScale', 'grades'));
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: grades for a given pay scale
    |--------------------------------------------------------------------------
    | Used by the create AND edit forms when the pay scale selector changes.
    */
    public function gradesByPayScale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pay_scale_id' => ['required', 'integer', 'exists:pay_scales,id'],
        ]);

        $payScale = PayScale::find($validated['pay_scale_id']);

        $grades = $payScale
            ? $payScale->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            : collect();

        return response()->json(['grades' => $grades]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: steps (basic salary) for a grade
    |--------------------------------------------------------------------------
    | pay_scale_id is REQUIRED for the multi-scale create/edit form.
    | Falls back to the single active scale only when omitted (legacy support).
    */
    public function stepsByGrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade'        => ['required', 'integer', 'min:1', 'max:20'],
            'pay_scale_id' => ['nullable', 'integer', 'exists:pay_scales,id'],
        ]);

        $payScale = ! empty($validated['pay_scale_id'])
            ? PayScale::find($validated['pay_scale_id'])
            : PayScale::active()->first();

        if (! $payScale) {
            return response()->json(['steps' => []], 200);
        }

        $steps = $payScale->steps()
            ->where('grade', $validated['grade'])
            ->orderBy('basic_salary')
            ->get(['id', 'grade', 'step', 'basic_salary']);

        return response()->json(['steps' => $steps]);
    }

    /**
     * Store a new employee.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse | RedirectResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $employee = Employee::create([
                'cpf_account_no'    => $request->validated('cpf_account_no'),
                'name'              => $request->validated('name'),
                'designation'       => $request->validated('designation'),
                'email'             => $request->validated('email'),
                'mobile_number'     => $request->validated('mobile_number'),
                'joining_date'      => $request->validated('joining_date'),
                'retirement_date'   => $request->validated('retirement_date'),
                'pay_scale_step_id' => $request->validated('pay_scale_step_id'),
            ]);

            // ── PHOTO UPLOAD ──────────────────────────────────────────────────
            if (
                $request->hasFile('photo') &&
                $request->file('photo')->isValid() &&
                $request->file('photo')->getSize() > 0
            ) {
                $file      = $request->file('photo');
                $extension = $file->getClientOriginalExtension();
                $filename  = 'emp_' . $employee->id . '_photo_' . time() . '.' . $extension;
                $uploadDir = public_path('uploads/employees/photos');

                if (! file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $file->move($uploadDir, $filename);
                $employee->update(['photo' => 'uploads/employees/photos/' . $filename]);
            }

// ── SALARY HISTORY — initial entry ───────────────────────────────
            EmployeeSalaryHistory::create([
                'employee_id'       => $employee->id,
                'pay_scale_step_id' => $employee->pay_scale_step_id,
                'effective_date'    => $employee->created_at->toDateString(),
                'change_type'       => 'initial',
                'remarks'           => 'Initial pay scale step on employee creation.',
            ]);

            // ── CPF OPENING BALANCE + LEDGER ─────────────────────────────────
            $self     = (int) $request->validated('opening_employee_contribution');
            $govt     = (int) $request->validated('opening_government_contribution');
            $interest = (int) $request->validated('opening_bank_interest');
            $net      = $self + $govt + $interest;

            $openingBalance = CpfOpeningBalance::create([
                'employee_id'             => $employee->id,
                'effective_date'          => $request->validated('opening_effective_date'),
                'self_contribution'       => $self,
                'government_contribution' => $govt,
                'interest_amount'         => $interest,
                'outstanding_advance'     => 0,
                'net_balance'             => $net,
                'remarks'                 => 'Opening balance captured at employee onboarding.',
            ]);

            // Single OPENING_BALANCE credit posted through the ledger service so
            // the running balance stays consistent. created_by auto-fills via
            // HasCreatedBy during this authenticated request.
            $this->ledgerService->create([
                'employee_id'      => $employee->id,
                'transaction_date' => $openingBalance->effective_date,
                'transaction_type' => LedgerTransactionType::OPENING_BALANCE,
                'source_type'      => SourceType::OPENING_BALANCE->value,
                'source_id'        => $openingBalance->id,
                'reference_no'     => null,
                'remarks'          => 'Opening balance',
                'credit'           => $net,
                'debit'            => 0,
            ]);

            return $employee;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Employee created successfully.',
                'employee' => [
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'cpf_account_no' => $employee->cpf_account_no,
                ],
            ], 201);
        }

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Show the full employee profile (tabbed view).
     *
     * Loads everything the system holds about the employee: profile, opening
     * balance, contributions, running ledger, advances + recoveries, bank
     * interest distributions, salary history. The activity log is fetched
     * separately via AJAX (see activities()).
     */
    public function show(Employee $employee): View
    {
        $employee->load([
            'payScaleStep.payScale',
            'openingBalance',
            'salaryHistories.payScaleStep',
            'advances' => fn($q) => $q->latest('application_date'),
            'advances.recoveries.creator',
            'advances.approver',
            'interestDistributions.batch',
            'contributions.batch',
            'ledgers'  => fn($q)  => $q->with('creator')->orderByDesc('created_at')->orderByDesc('id'),
            'creator',
        ]);

        // NOTE: ledger summary values are read straight from the database
        // (aggregate queries) so they never depend on eager-load state.
        $currentBalance            = $employee->currentBalance();
        $ledgerCredits             = (int) $employee->ledgers()->sum('credit');
        $ledgerDebits              = (int) $employee->ledgers()->sum('debit');
        $outstandingAdvance        = (int) $employee->advances->sum('outstanding_amount');
        $totalEmployeeContribution = (int) $employee->contributions->sum('employee_contribution');
        $totalGovtContribution     = (int) $employee->contributions->sum('government_contribution');
        $totalBankInterest         = (int) $employee->interestDistributions->sum('interest_amount');

        $activityCount = $this->buildActivityQuery($this->activitySubjectMap($employee))->count();

        // Distinct fiscal years + transaction types present in the ledger,
        // used to populate the Running Ledger filter dropdown.
        $ledgerFiscalYears = $employee->ledgers
            ->map(fn($l) => $l->transaction_date ? FiscalYearService::fromDate($l->transaction_date) : null)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $ledgerTypes = $employee->ledgers
            ->map(fn($l) => $l->transaction_type?->value)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('employees.show', compact(
            'employee',
            'currentBalance',
            'ledgerCredits',
            'ledgerDebits',
            'outstandingAdvance',
            'totalEmployeeContribution',
            'totalGovtContribution',
            'totalBankInterest',
            'activityCount',
            'ledgerFiscalYears',
            'ledgerTypes',
        ));
    }

    /**
     * DataTables (server-side) endpoint for the employee activity log.
     *
     * Aggregates Spatie activity for the employee AND its owned records
     * (opening balance, salary history, advances, recoveries).
     */
    public function activities(Request $request, Employee $employee): JsonResponse
    {
        $employee->load([
            'openingBalance:id,employee_id',
            'salaryHistories:id,employee_id',
            'advances:id,employee_id',
            'advances.recoveries:id,cpf_advance_id',
        ]);

        $map  = $this->activitySubjectMap($employee);
        $base = $this->buildActivityQuery($map);

        $recordsTotal = (clone $base)->count();

        // ── event / subject dropdown filters ─────────────────────────────
        if (! empty($request->input('event'))) {
            $base->where('event', $request->input('event'));
        }
        if ($morph = $this->subjectMorphFromToken($request->input('subject'))) {
            $base->where('subject_type', $morph);
        }

        // ── global search ────────────────────────────────────────────────
        $search = (string) $request->input('search.value', '');
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // ── ordering (column index → DB column) ──────────────────────────
        $orderable     = [null, 'description', 'event', 'subject_type', null, null, 'created_at'];
        $orderColIndex = (int) $request->input('order.0.column', 6);
        $orderDir      = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn   = $orderable[$orderColIndex] ?? 'created_at';
        $base->orderBy($orderColumn ?: 'created_at', $orderColumn ? $orderDir : 'desc');

        // ── pagination ───────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $data = $base->with('causer')->get()->map(function (Activity $activity) {
            return [
                'description' => Str::headline((string) ($activity->description ?? '')),
                'event'       => $activity->event,
                'subject'     => class_basename($activity->subject_type ?? '') ?: '—',
                'changes'     => $this->renderActivityChanges($activity),
                'causer'      => $activity->causer?->name ?? 'System',
                'when'        => optional($activity->created_at)->diffForHumans(),
                'when_exact'  => optional($activity->created_at)->format('h:i:s A, d-M-Y'),
                'when_ts'     => optional($activity->created_at)->timestamp,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Map a Subject filter token to its morph class.
     */
    private function subjectMorphFromToken(?string $token): ?string
    {
        return match ($token) {
            'employee'        => (new Employee)->getMorphClass(),
            'opening_balance' => (new CpfOpeningBalance)->getMorphClass(),
            'salary'          => (new EmployeeSalaryHistory)->getMorphClass(),
            'advance'         => (new CpfAdvance)->getMorphClass(),
            'recovery'        => (new CpfAdvanceRecovery)->getMorphClass(),
            default           => null,
        };
    }

    /**
     * Map of [morph class => subject ids] covering the employee and the
     * records it owns, for activity-log aggregation.
     */
    private function activitySubjectMap(Employee $employee): array
    {
        $map = [
            (new Employee)->getMorphClass() => [$employee->id],
        ];

        if ($employee->openingBalance) {
            $map[(new CpfOpeningBalance)->getMorphClass()] = [$employee->openingBalance->id];
        }

        $salaryHistoryIds = $employee->salaryHistories->pluck('id')->all();
        if ($salaryHistoryIds) {
            $map[(new EmployeeSalaryHistory)->getMorphClass()] = $salaryHistoryIds;
        }

        $advanceIds = $employee->advances->pluck('id')->all();
        if ($advanceIds) {
            $map[(new CpfAdvance)->getMorphClass()] = $advanceIds;
        }

        $recoveryIds = $employee->advances->flatMap(fn($a) => $a->recoveries->pluck('id'))->all();
        if ($recoveryIds) {
            $map[(new CpfAdvanceRecovery)->getMorphClass()] = $recoveryIds;
        }

        return $map;
    }

    /**
     * Base Activity query for a subject map.
     */
    private function buildActivityQuery(array $map)
    {
        return Activity::query()->where(function ($query) use ($map) {
            foreach ($map as $subjectType => $ids) {
                $query->orWhere(function ($sub) use ($subjectType, $ids) {
                    $sub->where('subject_type', $subjectType)
                        ->whereIn('subject_id', $ids);
                });
            }
        });
    }

    /**
     * Render the changed-attributes diff for an activity row as safe HTML.
     * Foreign keys are resolved to human labels and dates are formatted.
     */
    private function renderActivityChanges(Activity $activity): string
    {
        $attrs = data_get($activity->properties, 'attributes', []);
        $old   = data_get($activity->properties, 'old', []);

        if (empty($attrs)) {
            return '<span class="text-muted">—</span>';
        }

        $parts = [];
        foreach ($attrs as $key => $val) {
            $label = e($this->activityLabel($key));
            $new   = e($this->formatActivityValue($key, $val));

            if (array_key_exists($key, $old)) {
                $oldV    = e($this->formatActivityValue($key, $old[$key]));
                $parts[] = "<div class='fs-8 mb-1'><span class='fw-semibold text-gray-700'>{$label}:</span> "
                    . "<span class='text-danger'>{$oldV}</span> "
                    . "<i class='ki-outline ki-arrow-right fs-8 mx-1'></i>"
                    . "<span class='text-success'>{$new}</span></div>";
            } else {
                $parts[] = "<div class='fs-8 mb-1'><span class='fw-semibold text-gray-700'>{$label}:</span> "
                    . "<span class='text-success'>{$new}</span></div>";
            }
        }

        return "<div class='d-flex flex-column'>" . implode('', $parts) . '</div>';
    }

    /**
     * Friendly column label for an activity attribute key.
     */
    private function activityLabel(string $key): string
    {
        return match ($key) {
            'employee_id'       => 'Employee',
            'pay_scale_step_id' => 'Basic Salary',
            'cpf_advance_id'    => 'Advance',
            'created_by'        => 'Created By',
            'approved_by'       => 'Approved By',
            'submitted_by'      => 'Submitted By',
            'updated_by'        => 'Updated By',
            default             => Str::headline($key),
        };
    }

    /**
     * Format an activity attribute value: resolve references, booleans, dates.
     */
    private function formatActivityValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '∅';
        }

        if ($key === 'is_active') {
            return $value ? 'Active' : 'Inactive';
        }

        if ($key === 'status') {
            return Str::headline((string) $value);
        }

        // Foreign-key reference → human label.
        $resolved = $this->resolveReference($key, $value);
        if ($resolved !== null) {
            return $resolved;
        }

        // ISO date / datetime → "09-Jun-2026, 12:00:00 AM" in app timezone.
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?/', $value)) {
            try {
                return Carbon::parse($value)
                    ->timezone(config('app.timezone'))
                    ->format('d-M-Y, h:i:s A');
            } catch (\Throwable $e) {
                // fall through to raw value
            }
        }

        return (string) $value;
    }

    /**
     * Resolve a foreign-key attribute to a display label (cached per request).
     * Returns null when the key is not a known reference.
     */
    private function resolveReference(string $key, $value): ?string
    {
        $cacheKey = $key . ':' . $value;
        if (array_key_exists($cacheKey, $this->refCache)) {
            return $this->refCache[$cacheKey];
        }

        $label = match ($key) {
            'employee_id'       => optional(Employee::withTrashed()->find($value))->name,
            'pay_scale_step_id' => $this->payScaleStepLabel($value),
            'cpf_advance_id'    => optional(CpfAdvance::withTrashed()->find($value))->advance_no,
            'created_by',
            'approved_by',
            'submitted_by',
            'updated_by'        => optional(User::find($value))->name,
            default             => null,
        };

        return $this->refCache[$cacheKey] = $label;
    }

    /**
     * "৳45,610 (Grade 8, Step 14)" label for a pay scale step id.
     */
    private function payScaleStepLabel($value): ?string
    {
        $step = PayScaleStep::find($value);

        if (! $step) {
            return null;
        }

        return '৳' . number_format($step->basic_salary)
        . ' (Grade ' . $step->grade . ', Step ' . $step->step . ')';
    }

    /**
     * Download the employee's CPF ledger as an Excel workbook.
     * Honours the on-screen year / month / search filter via query params.
     */
    public function ledgerExcel(Request $request, Employee $employee): BinaryFileResponse
    {
        $employee->load('payScaleStep.payScale');

        $ledgers = $this->ledgerQuery($employee, $this->ledgerFilters($request))->get();

        return Excel::download(
            new EmployeeLedgerExport($ledgers, $employee),
            $this->ledgerFilename($employee, 'xlsx'),
        );
    }

    /**
     * Download the employee's CPF ledger as a PDF.
     * Honours the on-screen year / month / search filter via query params.
     */
    public function ledgerPdf(Request $request, Employee $employee): Response
    {
        $employee->load('payScaleStep');

        $filters        = $this->ledgerFilters($request);
        $ledgers        = $this->ledgerQuery($employee, $filters)->get();
        $currentBalance = $employee->currentBalance();

        $pdf = Pdf::loadView('employees.ledger-pdf', compact('employee', 'ledgers', 'currentBalance', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($this->ledgerFilename($employee, 'pdf'));
    }

    /**
     * Normalise the ledger filter query params.
     */
    private function ledgerFilters(Request $request): array
    {
        return [
            'fiscal_year' => $request->query('fiscal_year'),
            'type'        => $request->query('type'),
            'month'       => $request->query('month'),
            'search'      => trim((string) $request->query('search', '')),
        ];
    }

    /**
     * Build the (optionally filtered) ledger query — the single source of
     * truth shared by the on-screen table order and both export formats.
     */
    private function ledgerQuery(Employee $employee, array $filters)
    {
        $query = $employee->ledgers()
            ->with('creator')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['fiscal_year'])) {
            $query->whereBetween('transaction_date', [
                FiscalYearService::startDate($filters['fiscal_year']),
                FiscalYearService::endDate($filters['fiscal_year']),
            ]);
        }

        if (! empty($filters['type'])) {
            $query->where('transaction_type', $filters['type']);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('transaction_date', $filters['month']);
        }

        if (! empty($filters['search'])) {
            $term     = $filters['search'];
            $typeTerm = str_replace(' ', '_', strtolower($term));

            $query->where(function ($w) use ($term, $typeTerm) {
                $w->where('reference_no', 'like', "%{$term}%")
                    ->orWhere('remarks', 'like', "%{$term}%")
                    ->orWhere('transaction_type', 'like', "%{$typeTerm}%")
                    ->orWhere('source_type', 'like', "%{$typeTerm}%");
            });
        }

        return $query;
    }

    /**
     * Build a filesystem-safe download filename (CPF account numbers contain
     * slashes, e.g. "PRA/K/94/36").
     */
    private function ledgerFilename(Employee $employee, string $extension): string
    {
        $account = str_replace(['/', '\\', ' '], '-', $employee->cpf_account_no);

        return 'cpf-ledger-' . $account . '-' . now()->format('Ymd_His') . '.' . $extension;
    }

    /**
     * Show the employee edit form.
     */
    public function edit(Request $request, Employee $employee): View | RedirectResponse
    {
        if ($employee->isFinallySettled()) {
            return redirect()->route('employees.show', $employee)
                ->with('error', 'This employee has been finally settled and can no longer be edited.');
        }

        $user = $request->user();
        $employee->load('payScaleStep');

        $assignedStep  = $employee->payScaleStep;
        $assignedScale = $assignedStep
            ? PayScale::find($assignedStep->pay_scale_id)
            : null;

        $assignedScaleId   = $assignedScale?->id;
        $assignedScaleName = $assignedScale?->name;
        $assignedActive    = (bool) ($assignedScale?->is_active);
        $multipleActive    = PayScale::active()->count() > 1;
        $isAdmin           = $user->isAdmin();
        $isCpf             = $user->isCpfOfficer();

        $canChangePayScale = $isAdmin && ($multipleActive || ! $assignedActive);

        $canChangeGradeSalary = $assignedActive && ($isAdmin || $isCpf);

        $payScales = PayScale::orderByDesc('is_active')
            ->orderByDesc('effective_year')
            ->get(['id', 'name', 'is_active', 'effective_year']);

        $grades = ($assignedScale && $assignedActive)
            ? $assignedScale->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            : collect();

        $currentGrade  = $assignedStep?->grade;
        $currentStepId = $assignedStep?->id;

        $steps = ($assignedActive && $canChangeGradeSalary && $assignedScale && $currentGrade !== null)
            ? $assignedScale->steps()
            ->where('grade', $currentGrade)
            ->orderBy('basic_salary')
            ->get(['id', 'grade', 'step', 'basic_salary'])
            : collect();

        return view('employees.edit', compact(
            'employee',
            'payScales',
            'assignedScaleId',
            'assignedScaleName',
            'assignedActive',
            'grades',
            'steps',
            'currentGrade',
            'currentStepId',
            'canChangePayScale',
            'canChangeGradeSalary',
        ));
    }

    /**
     * Update employee details + handle photo replacement.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse | RedirectResponse
    {
        if ($employee->isFinallySettled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This employee has been finally settled and can no longer be edited.',
                ], 403);
            }

            return redirect()->route('employees.show', $employee)
                ->with('error', 'This employee has been finally settled and can no longer be edited.');
        }

        $this->authorizePayScaleChange($request, $employee);

        DB::transaction(function () use ($request, $employee) {
            $employee->update([
                'cpf_account_no'    => $request->validated('cpf_account_no'),
                'name'              => $request->validated('name'),
                'designation'       => $request->validated('designation'),
                'email'             => $request->validated('email'),
                'mobile_number'     => $request->validated('mobile_number'),
                'joining_date'      => $request->validated('joining_date'),
                'retirement_date'   => $request->validated('retirement_date'),
                'pay_scale_step_id' => $request->validated('pay_scale_step_id'),
            ]);

            if (
                $request->hasFile('photo') &&
                $request->file('photo')->isValid() &&
                $request->file('photo')->getSize() > 0
            ) {
                if ($employee->photo && file_exists(public_path($employee->photo))) {
                    @unlink(public_path($employee->photo));
                }

                $file      = $request->file('photo');
                $extension = $file->getClientOriginalExtension();
                $filename  = 'emp_' . $employee->id . '_photo_' . time() . '.' . $extension;
                $uploadDir = public_path('uploads/employees/photos');

                if (! file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $file->move($uploadDir, $filename);
                $employee->update(['photo' => 'uploads/employees/photos/' . $filename]);
            }

            if ($request->input('photo_remove') == '1') {
                if ($employee->photo && file_exists(public_path($employee->photo))) {
                    @unlink(public_path($employee->photo));
                }
                $employee->update(['photo' => null]);
            }

            if ($employee->wasChanged('pay_scale_step_id')) {
                EmployeeSalaryHistory::create([
                    'employee_id'       => $employee->id,
                    'pay_scale_step_id' => $employee->pay_scale_step_id,
                    'effective_date'    => now()->toDateString(),
                    'change_type'       => 'revision',
                    'remarks'           => 'Pay scale step revised via employee edit.',
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Employee updated successfully.',
                'employee' => [
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'cpf_account_no' => $employee->cpf_account_no,
                ],
            ]);
        }

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Server-side authorization guard for pay-scale / grade / salary changes.
     */
    private function authorizePayScaleChange(UpdateEmployeeRequest $request, Employee $employee): void
    {
        $user          = $request->user();
        $currentStepId = (int) $employee->pay_scale_step_id;
        $chosenStepId  = (int) $request->validated('pay_scale_step_id');

        if ($chosenStepId === $currentStepId) {
            return;
        }

        $chosenStep = PayScaleStep::find($chosenStepId);

        if (! $chosenStep) {
            throw ValidationException::withMessages([
                'pay_scale_step_id' => 'The selected basic salary is invalid.',
            ]);
        }

        $assignedStep   = $employee->payScaleStep;
        $assignedScale  = $assignedStep ? PayScale::find($assignedStep->pay_scale_id) : null;
        $assignedActive = (bool) ($assignedScale?->is_active);

        $scaleChanged = ! $assignedScale
            || ((int) $chosenStep->pay_scale_id !== (int) $assignedScale->id);

        if ($scaleChanged) {
            if (! $user->isAdmin()) {
                throw ValidationException::withMessages([
                    'pay_scale_step_id' => 'Only an administrator can change the pay scale.',
                ]);
            }

            $targetScale = PayScale::find($chosenStep->pay_scale_id);

            if (! $targetScale || ! $targetScale->is_active) {
                throw ValidationException::withMessages([
                    'pay_scale_step_id' => 'The selected pay scale is inactive and cannot be assigned.',
                ]);
            }

            return;
        }

        if (! $assignedActive) {
            throw ValidationException::withMessages([
                'pay_scale_step_id' =>
                'The assigned pay scale is inactive. Select a new active pay scale first.',
            ]);
        }

        if (! ($user->isAdmin() || $user->isCpfOfficer())) {
            throw ValidationException::withMessages([
                'pay_scale_step_id' => 'You are not permitted to change the grade or basic salary.',
            ]);
        }
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->isFinallySettled()) {
            return redirect()->route('employees.show', $employee)
                ->with('error', 'This employee has been finally settled and cannot be deleted.');
        }

        // Safety guard: never delete an employee that still has a CPF balance.
        if ($employee->currentBalance() !== 0) {
            return redirect()->route('employees.show', $employee)
                ->with('error', 'Employee cannot be deleted while the CPF balance is non-zero.');
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * AJAX toggle active/inactive status.
     */
    public function toggleActive(Request $request): JsonResponse
    {
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $employee = Employee::findOrFail($request->employee_id);

        if ($employee->isFinallySettled()) {
            return response()->json([
                'success' => false,
                'message' => 'This employee has been finally settled and cannot be reactivated or deactivated.',
            ], 403);
        }

        $employee->update(['is_active' => ! $employee->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $employee->is_active,
        ]);
    }
}
