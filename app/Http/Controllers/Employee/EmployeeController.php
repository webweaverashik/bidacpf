<?php
namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $payScale = PayScale::active()->first();
        $grades   = $payScale?->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade');

        return view('employees.create', compact('payScale', 'grades'));
    }

    public function stepsByGrade(Request $request): JsonResponse
    {
        $request->validate(['grade' => 'required|integer|min:1|max:20']);

        $payScale = PayScale::active()->first();

        if (! $payScale) {
            return response()->json(['steps' => []], 200);
        }

        $steps = $payScale->steps()
            ->where('grade', $request->integer('grade'))
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

            // ──────────────────────────────────────────────────────────────
            // PHOTO UPLOAD
            // ──────────────────────────────────────────────────────────────
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
            // ──────────────────────────────────────────────────────────────

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

    public function edit(Employee $employee): View
    {
        $payScale = PayScale::active()->first();
        $grades   = $payScale?->steps()
            ->select('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade');

        return view('employees.edit', compact('employee', 'payScale', 'grades'));
    }

    /**
     * Update employee details + handle photo replacement.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse | RedirectResponse
    {
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

            // ──────────────────────────────────────────────────────────────
            // PHOTO: replace only when a real new file is uploaded
            // ──────────────────────────────────────────────────────────────
            if (
                $request->hasFile('photo') &&
                $request->file('photo')->isValid() &&
                $request->file('photo')->getSize() > 0
            ) {
                // Delete the old photo from disk (if any)
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

            // ──────────────────────────────────────────────────────────────
            // PHOTO REMOVE: client sent photo_remove = 1
            // ──────────────────────────────────────────────────────────────
            if ($request->input('photo_remove') == '1') {
                if ($employee->photo && file_exists(public_path($employee->photo))) {
                    @unlink(public_path($employee->photo));
                }
                $employee->update(['photo' => null]);
            }

            // ──────────────────────────────────────────────────────────────
            // PAY SCALE STEP: record salary history if the step changed
            // ──────────────────────────────────────────────────────────────
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
