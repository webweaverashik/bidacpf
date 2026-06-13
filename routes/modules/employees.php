<?php

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Routes
|--------------------------------------------------------------------------
|
| CRUD + detail pages for employees, plus the AJAX feeds used by the
| create/edit stepper (steps-by-grade, grades-by-pay-scale), the
| activate/deactivate toggle, the activity-log feed, and the per-employee
| ledger exports.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: every fixed-segment route (create, edit, AJAX feeds, exports)
| is registered before the `employees/{employee}` wildcard show route so
| the wildcard does not capture those paths.
*/

// ---- Per-employee ledger exports ----------------------------------------
Route::middleware('can:cpf_ledger.view')->group(function () {
    Route::get('employees/{employee}/ledger/pdf', [EmployeeController::class, 'ledgerPdf'])
        ->name('employees.ledger.pdf');
    Route::get('employees/{employee}/ledger/excel', [EmployeeController::class, 'ledgerExcel'])
        ->name('employees.ledger.excel');
});

// ---- AJAX feeds for the create/edit stepper (before resource routes) ----
// Load steps by grade.
Route::get('employees/steps-by-grade', [EmployeeController::class, 'stepsByGrade'])
    ->name('employees.steps-by-grade')
    ->middleware('permission:employee.create|employee.update');

// Load grades for a given pay scale.
Route::get('employees/grades-by-pay-scale', [EmployeeController::class, 'gradesByPayScale'])
    ->name('employees.grades-by-pay-scale')
    ->middleware('permission:employee.create|employee.update');

// ---- Activate / deactivate toggle ---------------------------------------
Route::post('employees/toggle-active', [EmployeeController::class, 'toggleActive'])
    ->middleware('can:employee.update')
    ->name('employees.toggleActive');

// ---- Activity-log feed ---------------------------------------------------
Route::get('employees/{employee}/activities', [EmployeeController::class, 'activities'])
    ->middleware('can:employee.view')
    ->name('employees.activities');

// ---- Server-side DataTable feed + filter-aware exports -------------------
Route::middleware('can:employee.view')->group(function () {
    Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
});

// ---- Listing -------------------------------------------------------------
Route::middleware('can:employee.view')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
});

// ---- Create --------------------------------------------------------------
Route::middleware('can:employee.create')->group(function () {
    Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
});

// ---- Update --------------------------------------------------------------
Route::middleware('can:employee.update')->group(function () {
    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
});

// ---- Delete (balance-gated in controller) -------------------------------
Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
    ->middleware('can:employee.delete')
    ->name('employees.destroy');

// ---- Detail (wildcard — keep LAST among employees GETs) -----------------
Route::get('employees/{employee}', [EmployeeController::class, 'show'])
    ->middleware('can:employee.view')
    ->name('employees.show');
