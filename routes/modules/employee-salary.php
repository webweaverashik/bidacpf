<?php

use App\Http\Controllers\Employee\EmployeeSalaryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Salary Routes
|--------------------------------------------------------------------------
|
| Salary assignment, annual increment, and the per-employee salary detail
| page, plus the server-side DataTable feed and the cascading filter feeds
| (Pay Scale -> Grade -> Basic Salary).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the DataTable/filter feeds and the assign route are declared
| before the `employee-salary/{employee}` wildcard show route.
*/

// ---- Read-only page + server-side feeds ---------------------------------
Route::middleware('can:employee_salary.view')->group(function () {
    Route::get('employee-salary', [EmployeeSalaryController::class, 'index'])->name('employee-salary.index');

    // Server-side DataTable feed.
    Route::get('employee-salary/data', [EmployeeSalaryController::class, 'data'])->name('employee-salary.data');

    // Cascading filter feeds (Pay Scale -> Grade -> Basic Salary).
    Route::get('employee-salary/filter/grades', [EmployeeSalaryController::class, 'filterGrades'])->name('employee-salary.filter.grades');
    Route::get('employee-salary/filter/steps', [EmployeeSalaryController::class, 'filterSteps'])->name('employee-salary.filter.steps');
});

// ---- Assign salary -------------------------------------------------------
Route::middleware('can:employee_salary.create')->group(function () {
    Route::get('employee-salary/{employee}/assign', [EmployeeSalaryController::class, 'create'])->name('employee-salary.create');
    Route::post('employee-salary/{employee}', [EmployeeSalaryController::class, 'store'])->name('employee-salary.store');
});

// ---- Annual increment ----------------------------------------------------
Route::put('employee-salary/{employee}/increment', [EmployeeSalaryController::class, 'increment'])
    ->middleware('can:employee_salary.update')
    ->name('employee-salary.increment');

// ---- Detail (wildcard — keep LAST among employee-salary GETs) -----------
Route::get('employee-salary/{employee}', [EmployeeSalaryController::class, 'show'])
    ->middleware('can:employee_salary.view')
    ->name('employee-salary.show');
