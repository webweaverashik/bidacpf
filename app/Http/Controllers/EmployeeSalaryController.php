<?php
namespace App\Http\Controllers;

use App\Http\Requests\EmployeeSalary\UpdateSalaryStepRequest;
use App\Models\Employee;
use App\Models\PayScale;
use App\Models\PayScaleStep;
use App\Services\EmployeeSalaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeSalaryController extends Controller
{
    public function __construct(protected EmployeeSalaryService $salaryService)
    {}

    public function index(): View
    {
        $employees = Employee::with('payScaleStep')
            ->search(request('search'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('employee-salary.index', compact('employees'));
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
