<?php
namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScale;
use App\Models\Employee\PayScaleStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with('payScaleStep')
            ->latest()
            ->get();

        return view('employees.index', compact('employees'));
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
                'status'            => $request->validated('status'),
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

    public function show(Employee $employee): View
    {
        $employee->load('payScaleStep', 'salaryHistories.payScaleStep');

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the employee edit form.
     *
     * Permission matrix:
     * ┌──────────────────────────────────────────┬───────┬─────────────┬─────────┐
     * │ Scenario                                 │ Admin │ CPF Officer │ Others  │
     * ├──────────────────────────────────────────┼───────┼─────────────┼─────────┤
     * │ Assigned scale ACTIVE, 1 active scale    │   G   │      G      │   –     │
     * │ Assigned scale ACTIVE, multi active      │  PS+G │      G      │   –     │
     * │ Assigned scale INACTIVE                  │  PS+G │      –      │   –     │
     * └──────────────────────────────────────────┴───────┴─────────────┴─────────┘
     * PS = may change pay scale | G = may change grade + basic salary
     *
     * Note on canChangeGradeSalary when assigned scale is INACTIVE:
     *   - For Admin: starts as false on page load (because the inactive scale's
     *     grades/steps are meaningless). Grade/salary selects become interactive
     *     AFTER the Admin picks a new active pay scale (handled in JS).
     *   - The server-side authorizePayScaleChange() guard handles the actual
     *     validation and accepts the new step from the Admin.
     */
    public function edit(Request $request, Employee $employee): View
    {
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

        /*
         * canChangePayScale:
         *   Admin can switch scale when:
         *     (a) there are multiple active scales, OR
         *     (b) the assigned scale is inactive (must migrate to a new active one)
         */
        $canChangePayScale = $isAdmin && ($multipleActive || ! $assignedActive);

        /*
         * canChangeGradeSalary (INITIAL state, used for blade rendering):
         *   - true  → grade and salary selects are enabled on page load.
         *   - false → grade and salary selects start disabled.
         *
         *   Rules:
         *   • Requires the assigned scale to be ACTIVE.
         *   • Admin OR CPF Officer.
         *   • Nobody when the assigned scale is inactive
         *     (Admin will unlock via JS after picking a new active scale).
         */
        $canChangeGradeSalary = $assignedActive && ($isAdmin || $isCpf);

        // All pay scales (active + inactive) for the pay-scale selector.
        $payScales = PayScale::orderByDesc('is_active')
            ->orderByDesc('effective_year')
            ->get(['id', 'name', 'is_active', 'effective_year']);

        // Grades for the currently assigned scale (pre-populate the dropdown for active scale).
        // When the assigned scale is inactive, we render no grade options —
        // JS will load them after the Admin selects a new active scale.
        $grades = ($assignedScale && $assignedActive)
            ? $assignedScale->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            : collect();

        $currentGrade  = $assignedStep?->grade;
        $currentStepId = $assignedStep?->id;

        // Pre-load steps for the current grade only when scale is active and user can edit.
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
                'status'            => $request->validated('status'),
            ]);

            // ── PHOTO REPLACEMENT ─────────────────────────────────────────────
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

            // ── PHOTO REMOVAL ─────────────────────────────────────────────────
            if ($request->input('photo_remove') == '1') {
                if ($employee->photo && file_exists(public_path($employee->photo))) {
                    @unlink(public_path($employee->photo));
                }
                $employee->update(['photo' => null]);
            }

            // ── SALARY HISTORY ────────────────────────────────────────────────
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
     *
     * Rules (mirrors the blade/JS permission logic):
     *   1. No change in step id → always OK.
     *   2. Pay scale changed    → must be Admin + target scale must be active.
     *   3. Grade/salary changed within same scale → Admin or CPF Officer,
     *      and the assigned scale must be active.
     *
     * Note: When the assigned scale is inactive and Admin provides a new step
     * from a DIFFERENT (active) scale, this falls under Rule 2 and is allowed.
     */
    private function authorizePayScaleChange(UpdateEmployeeRequest $request, Employee $employee): void
    {
        $user          = $request->user();
        $currentStepId = (int) $employee->pay_scale_step_id;
        $chosenStepId  = (int) $request->validated('pay_scale_step_id');

        // No change — nothing to authorize
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

        // Determine whether the pay scale itself is being changed.
        // Also treat a missing assigned scale as a scale change.
        $scaleChanged = ! $assignedScale
            || ((int) $chosenStep->pay_scale_id !== (int) $assignedScale->id);

        // ── Rule 2: Pay scale change ─────────────────────────────────────────
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

            return; // Admin + active target scale → OK
        }

        // ── Rule 3: Grade/salary change within same scale ────────────────────
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
        $employee->update(['is_active' => ! $employee->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $employee->is_active,
        ]);
    }
}
