<?php
namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeSalary\UpdateSalaryStepRequest;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScale;
use App\Models\Employee\PayScaleStep;
use App\Services\Employee\EmployeeSalaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeSalaryController extends Controller
{
    /** Whether the current user may follow the "Created By" link to a user. */
    private bool $canViewUsers = false;

    public function __construct(protected EmployeeSalaryService $salaryService)
    {}

    /**
     * Salary history index (server-side DataTable shell).
     *
     * Loads the pay scales for the cascading filter; the table rows themselves
     * arrive over AJAX via data().
     */
    public function index(): View
    {
        $payScales = PayScale::orderByDesc('is_active')
            ->orderByDesc('effective_year')
            ->get(['id', 'name', 'is_active', 'effective_year']);

        return view('employees.salary-history.index', compact('payScales'));
    }

    /**
     * Server-side DataTables endpoint for the salary history list.
     *
     * Handles search, change-type / pay-scale / grade / basic-salary filtering,
     * ordering and pagination on the server. created_by renders as "System"
     * when null (e.g. automatic annual increments posted by an observer).
     */
    public function data(Request $request): JsonResponse
    {
        $this->canViewUsers = (bool) $request->user()?->can('user.view');

        // Base query — join the related tables so we can search & order on
        // their columns without N+1 lookups. employees uses SoftDeletes, so
        // trashed members are excluded explicitly (joins bypass global scopes).
        $base = EmployeeSalaryHistory::query()
            ->join('employees', 'employee_salary_histories.employee_id', '=', 'employees.id')
            ->join('pay_scale_steps', 'employee_salary_histories.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->join('pay_scales', 'pay_scale_steps.pay_scale_id', '=', 'pay_scales.id')
            ->leftJoin('users', 'employee_salary_histories.created_by', '=', 'users.id')
            ->whereNull('employees.deleted_at')
            ->select([
                'employee_salary_histories.id',
                'employee_salary_histories.employee_id',
                'employee_salary_histories.pay_scale_step_id',
                'employee_salary_histories.effective_date',
                'employee_salary_histories.change_type',
                'employee_salary_histories.remarks',
                'employee_salary_histories.created_by',
                'employee_salary_histories.created_at',
                'employees.name as employee_name',
                'employees.cpf_account_no',
                'employees.designation',
                'pay_scales.name as pay_scale_name',
                'pay_scale_steps.pay_scale_id',
                'pay_scale_steps.grade',
                'pay_scale_steps.step',
                'pay_scale_steps.basic_salary',
                'users.name as creator_name',
            ]);

        $recordsTotal = (clone $base)->count();

        // ── filters ──────────────────────────────────────────────────────
        if ($changeType = $request->input('change_type')) {
            $base->where('employee_salary_histories.change_type', $changeType);
        }

        if (($payScaleId = $request->input('pay_scale_id')) !== null && $payScaleId !== '') {
            $base->where('pay_scale_steps.pay_scale_id', (int) $payScaleId);
        }

        if (($grade = $request->input('grade')) !== null && $grade !== '') {
            $base->where('pay_scale_steps.grade', (int) $grade);
        }

        if (($stepId = $request->input('pay_scale_step_id')) !== null && $stepId !== '') {
            $base->where('employee_salary_histories.pay_scale_step_id', (int) $stepId);
        }

        // ── global search ────────────────────────────────────────────────
        $search = (string) $request->input('search.value', '');
        if ($search !== '') {
            $typeTerm = str_replace(' ', '_', strtolower($search));

            $base->where(function ($q) use ($search, $typeTerm) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.designation', 'like', "%{$search}%")
                    ->orWhere('pay_scales.name', 'like', "%{$search}%")
                    ->orWhere('employee_salary_histories.remarks', 'like', "%{$search}%")
                    ->orWhere('employee_salary_histories.change_type', 'like', "%{$typeTerm}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // ── ordering (column index → DB column) ──────────────────────────
        $orderable = [
            null,                                       // 0  row #
            'employees.name',                           // 1  Employee
            'employees.designation',                    // 2  Designation
            'pay_scales.name',                          // 3  Pay Scale
            'pay_scale_steps.grade',                    // 4  Grade / Step
            'pay_scale_steps.basic_salary',             // 5  Basic Salary
            'employee_salary_histories.effective_date', // 6  Effective Date
            'employee_salary_histories.change_type',    // 7  Change Type
            'employee_salary_histories.remarks',        // 8  Remarks
            'users.name',                               // 9  Created By
            'employee_salary_histories.created_at',     // 10 Created At
        ];

        $orderColIndex = (int) $request->input('order.0.column', 10);
        $orderDir      = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn   = $orderable[$orderColIndex] ?? 'employee_salary_histories.created_at';

        if ($orderColumn) {
            $base->orderBy($orderColumn, $orderDir);
            // Secondary key on grade ordering so steps read naturally.
            if ($orderColumn === 'pay_scale_steps.grade') {
                $base->orderBy('pay_scale_steps.step', $orderDir);
            }
        } else {
            $base->orderByDesc('employee_salary_histories.created_at');
        }

        // ── pagination ───────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $data = $base->get()->map(function (EmployeeSalaryHistory $row) {
            return [
                'employee'       => $this->renderEmployee($row),
                'designation'    => e($row->designation ?: '—'),
                'pay_scale'      => e($row->pay_scale_name ?: '—'),
                'grade_step'     => 'Grade ' . $row->grade . ' &middot; Step ' . $row->step,
                'basic_salary'   => '৳' . number_format((int) $row->basic_salary),
                'effective_date' => $row->effective_date?->format('d M Y') ?? '—',
                'change_type'    => $this->renderChangeType($row->change_type),
                'remarks'        => $row->remarks ? e($row->remarks) : '<span class="text-muted">—</span>',
                'created_by'     => $this->renderCreatedBy($row),
                'created_at'     => $row->created_at?->format('h:i A, d M Y') ?? '—',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: cascading filter feeds
    |--------------------------------------------------------------------------
    | Gated under employee_salary.view (these mirror the employee create/edit
    | endpoints but are usable by anyone who can see the salary history list,
    | including the read-only Auditor role).
    */

    /**
     * Distinct grades available within a pay scale.
     */
    public function filterGrades(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pay_scale_id' => ['required', 'integer', 'exists:pay_scales,id'],
        ]);

        $grades = PayScaleStep::query()
            ->where('pay_scale_id', $validated['pay_scale_id'])
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade');

        return response()->json(['grades' => $grades]);
    }

    /**
     * Steps (basic salaries) available for a grade within a pay scale.
     */
    public function filterSteps(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pay_scale_id' => ['required', 'integer', 'exists:pay_scales,id'],
            'grade'        => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $steps = PayScaleStep::query()
            ->where('pay_scale_id', $validated['pay_scale_id'])
            ->where('grade', $validated['grade'])
            ->orderBy('basic_salary')
            ->get(['id', 'grade', 'step', 'basic_salary']);

        return response()->json(['steps' => $steps]);
    }

    /*
    |--------------------------------------------------------------------------
    | Row renderers
    |--------------------------------------------------------------------------
    */

    /**
     * Employee name + CPF account, linking to the profile.
     */
    private function renderEmployee(EmployeeSalaryHistory $row): string
    {
        $url = route('employees.show', $row->employee_id);

        return '<a href="' . $url . '" target="_blank" '
        . 'class="text-gray-800 text-hover-primary fw-bold">' . e($row->employee_name) . '</a>'
        . '<div class="fs-7 text-muted">' . e($row->cpf_account_no) . '</div>';
    }

    /**
     * Coloured badge for the change type.
     */
    private function renderChangeType(?string $type): string
    {
        $class = match ($type) {
            'initial'          => 'badge-light-primary',
            'annual_increment' => 'badge-light-success',
            'promotion'        => 'badge-light-info',
            'revision'         => 'badge-light-warning',
            default            => 'badge-light',
        };

        return '<span class="badge ' . $class . '">' . e(Str::headline((string) $type)) . '</span>';
    }

    /**
     * Creator: a "System" badge when null, otherwise the name — linked to the
     * user profile when the current viewer holds user.view.
     */
    private function renderCreatedBy(EmployeeSalaryHistory $row): string
    {
        if (! $row->creator_name) {
            return '<span class="badge badge-light-dark">System</span>';
        }

        $name = e($row->creator_name);

        if ($this->canViewUsers && $row->created_by) {
            $url = route('users.show', $row->created_by);

            return '<a href="' . $url . '" target="_blank" '
                . 'class="text-gray-800 text-hover-primary">' . $name . '</a>';
        }

        return $name;
    }

    public function show(Employee $employee): View
    {
        $employee->load('salaryHistories.payScaleStep');

        return view('employee-salary.show', compact('employee'));
    }

    public function create(Employee $employee): View
    {
        $payScale = PayScale::active()->first();
        $steps    = $payScale?->steps()
            ->where('grade', $employee->grade)
            ->orderBy('step')
            ->get();

        return view('employee-salary.create', compact('employee', 'payScale', 'steps'));
    }

    public function store(UpdateSalaryStepRequest $request, Employee $employee): RedirectResponse
    {
        $step = PayScaleStep::findOrFail($request->validated('pay_scale_step_id'));

        $this->salaryService->updateStep(
            employee: $employee,
            step: $step,
            changeType: $request->validated('change_type'),
            createdBy: auth()->id(),
            remarks: $request->validated('remarks'),
        );

        return redirect()->route('employee-salary.show', $employee)
            ->with('success', 'Salary step updated successfully.');
    }

    public function increment(Employee $employee): RedirectResponse
    {
        $this->salaryService->annualIncrement($employee, auth()->id());

        return redirect()->route('employee-salary.show', $employee)
            ->with('success', 'Annual increment applied successfully.');
    }
}
