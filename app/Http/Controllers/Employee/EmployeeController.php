<?php
namespace App\Http\Controllers\Employee;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\PayScale;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with('payScaleStep')
            ->search(request('search'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        $payScale = PayScale::active()->first();
        $grades   = $payScale?->steps()->select('grade')->distinct()->orderBy('grade')->pluck('grade');

        return view('employees.create', compact('payScale', 'grades'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): View
    {
        $employee->load('payScaleStep', 'salaryHistories.payScaleStep', 'openingBalance');

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $payScale = PayScale::active()->first();
        $grades   = $payScale?->steps()->select('grade')->distinct()->orderBy('grade')->pluck('grade');

        return view('employees.edit', compact('employee', 'payScale', 'grades'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
