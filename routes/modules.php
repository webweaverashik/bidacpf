<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Cpf\CpfAdvanceController;
use App\Http\Controllers\Cpf\CpfAdvanceRecoveryController;
use App\Http\Controllers\Cpf\CpfContributionController;
use App\Http\Controllers\Cpf\CpfLedgerController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeSalaryController;
use App\Http\Controllers\Interest\BankInterestController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'isLoggedIn'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Employees
    |--------------------------------------------------------------------------
    */

    Route::middleware('can:cpf_ledger.view')->group(function () {
        Route::get('employees/{employee}/ledger/pdf', [EmployeeController::class, 'ledgerPdf'])
            ->name('employees.ledger.pdf');
        Route::get('employees/{employee}/ledger/excel', [EmployeeController::class, 'ledgerExcel'])
            ->name('employees.ledger.excel');
    });

    // AJAX endpoint: load steps by grade (must be before resource route).
    Route::get('employees/steps-by-grade', [EmployeeController::class, 'stepsByGrade'])
        ->name('employees.steps-by-grade')
        ->middleware('permission:employee.create|employee.update');

    // NEW — AJAX endpoint: load grades for a given pay scale.
    Route::get('employees/grades-by-pay-scale', [EmployeeController::class, 'gradesByPayScale'])
        ->name('employees.grades-by-pay-scale')
        ->middleware('permission:employee.create|employee.update');

    Route::post('employees/toggle-active', [EmployeeController::class, 'toggleActive'])
        ->middleware('can:employee.update')
        ->name('employees.toggleActive');

    Route::get('employees/{employee}/activities', [EmployeeController::class, 'activities'])
        ->middleware('can:employee.view')
        ->name('employees.activities');

    Route::middleware('can:employee.view')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    });

    Route::middleware('can:employee.create')->group(function () {
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
    });

    Route::middleware('can:employee.update')->group(function () {
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
        ->middleware('can:employee.delete')
        ->name('employees.destroy');

    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('can:employee.view')
        ->name('employees.show');

    /*
    |--------------------------------------------------------------------------
    | Employee Salary
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:employee_salary.view')->group(function () {
        Route::get('employee-salary', [EmployeeSalaryController::class, 'index'])->name('employee-salary.index');

        // Server-side DataTable feed.
        Route::get('employee-salary/data', [EmployeeSalaryController::class, 'data'])->name('employee-salary.data');

        // Cascading filter feeds (Pay Scale → Grade → Basic Salary).
        Route::get('employee-salary/filter/grades', [EmployeeSalaryController::class, 'filterGrades'])->name('employee-salary.filter.grades');
        Route::get('employee-salary/filter/steps', [EmployeeSalaryController::class, 'filterSteps'])->name('employee-salary.filter.steps');
    });

    Route::middleware('can:employee_salary.create')->group(function () {
        Route::get('employee-salary/{employee}/assign', [EmployeeSalaryController::class, 'create'])->name('employee-salary.create');
        Route::post('employee-salary/{employee}', [EmployeeSalaryController::class, 'store'])->name('employee-salary.store');
    });

    Route::put('employee-salary/{employee}/increment', [EmployeeSalaryController::class, 'increment'])
        ->middleware('can:employee_salary.update')
        ->name('employee-salary.increment');

    Route::get('employee-salary/{employee}', [EmployeeSalaryController::class, 'show'])
        ->middleware('can:employee_salary.view')
        ->name('employee-salary.show');

    /*
    |--------------------------------------------------------------------------
    | CPF Contributions
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:cpf_contribution.view')->group(function () {
        Route::get('cpf-contributions', [CpfContributionController::class, 'index'])->name('cpf-contributions.index');
    });

    Route::post('cpf-contributions', [CpfContributionController::class, 'store'])
        ->middleware('can:cpf_contribution.create')
        ->name('cpf-contributions.store');

    Route::put('cpf-contributions/{batch}/submit', [CpfContributionController::class, 'submit'])
        ->middleware('can:cpf_contribution.submit')
        ->name('cpf-contributions.submit');

    Route::put('cpf-contributions/{batch}/reverse', [CpfContributionController::class, 'reverse'])
        ->middleware('can:cpf_contribution.reverse')
        ->name('cpf-contributions.reverse');

    Route::get('cpf-contributions/{batch}', [CpfContributionController::class, 'show'])
        ->middleware('can:cpf_contribution.view')
        ->name('cpf-contributions.show');

    /*
    |--------------------------------------------------------------------------
    | CPF Ledger
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:cpf_ledger.view')->group(function () {
        Route::get('cpf-ledger', [CpfLedgerController::class, 'index'])->name('cpf-ledger.index');
        Route::get('cpf-ledger/transactions', [CpfLedgerController::class, 'transactions'])->name('cpf-ledger.transactions');
        Route::get('cpf-ledger/{employee}', [CpfLedgerController::class, 'show'])->name('cpf-ledger.show');
    });

    /*
    |--------------------------------------------------------------------------
    | CPF Advances
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:cpf_advance.view')->group(function () {
        Route::get('cpf-advances', [CpfAdvanceController::class, 'index'])->name('cpf-advances.index');
        Route::get('cpf-advances/outstanding', [CpfAdvanceController::class, 'outstanding'])->name('cpf-advances.outstanding');
    });

    Route::middleware('can:cpf_advance.create')->group(function () {
        Route::get('cpf-advances/create', [CpfAdvanceController::class, 'create'])->name('cpf-advances.create');
        Route::post('cpf-advances', [CpfAdvanceController::class, 'store'])->name('cpf-advances.store');
    });

    Route::middleware('can:cpf_advance.recovery')->group(function () {
        Route::get('cpf-advances/recovery', [CpfAdvanceRecoveryController::class, 'index'])->name('cpf-advances.recovery.index');
        Route::get('cpf-advances/{advance}/recovery/create', [CpfAdvanceRecoveryController::class, 'create'])->name('cpf-advances.recovery.create');
        Route::post('cpf-advances/{advance}/recovery', [CpfAdvanceRecoveryController::class, 'store'])->name('cpf-advances.recovery.store');
    });

    Route::put('cpf-advances/{advance}/approve', [CpfAdvanceController::class, 'approve'])
        ->middleware('can:cpf_advance.approve')
        ->name('cpf-advances.approve');

    Route::put('cpf-advances/{advance}/cancel', [CpfAdvanceController::class, 'cancel'])
        ->middleware('can:cpf_advance.approve')
        ->name('cpf-advances.cancel');

    Route::get('cpf-advances/{advance}', [CpfAdvanceController::class, 'show'])
        ->middleware('can:cpf_advance.view')
        ->name('cpf-advances.show');

    /*
    |--------------------------------------------------------------------------
    | Bank Interest
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:bank_interest.view')->group(function () {
        Route::get('bank-interest', [BankInterestController::class, 'index'])->name('bank-interest.index');
    });

    Route::middleware('can:bank_interest.create')->group(function () {
        Route::get('bank-interest/distribute', [BankInterestController::class, 'distribute'])->name('bank-interest.distribute');
        Route::post('bank-interest', [BankInterestController::class, 'store'])->name('bank-interest.store');
        Route::post('bank-interest/{batch}/generate', [BankInterestController::class, 'generate'])->name('bank-interest.generate');
    });

    Route::put('bank-interest/{batch}/submit', [BankInterestController::class, 'submit'])
        ->middleware('can:bank_interest.submit')
        ->name('bank-interest.submit');

    Route::put('bank-interest/{batch}/reverse', [BankInterestController::class, 'reverse'])
        ->middleware('can:bank_interest.reverse')
        ->name('bank-interest.reverse');

    Route::get('bank-interest/{batch}', [BankInterestController::class, 'show'])
        ->middleware('can:bank_interest.view')
        ->name('bank-interest.show');

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:report.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/account-slip', [ReportController::class, 'accountSlip'])->name('reports.account-slip');
        Route::get('reports/ledger-statement', [ReportController::class, 'ledgerStatement'])->name('reports.ledger-statement');
        Route::get('reports/contribution-summary', [ReportController::class, 'contributionSummary'])->name('reports.contribution-summary');
        Route::get('reports/advance-summary', [ReportController::class, 'advanceSummary'])->name('reports.advance-summary');
    });

    Route::middleware('can:report.export')->group(function () {
        Route::get('reports/account-slip/export', [ReportController::class, 'exportAccountSlip'])->name('reports.account-slip.export');
        Route::get('reports/ledger-statement/export', [ReportController::class, 'exportLedgerStatement'])->name('reports.ledger-statement.export');
        Route::get('reports/contribution-summary/export', [ReportController::class, 'exportContributionSummary'])->name('reports.contribution-summary.export');
        Route::get('reports/advance-summary/export', [ReportController::class, 'exportAdvanceSummary'])->name('reports.advance-summary.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::get('settings', [SettingController::class, 'index'])
        ->middleware('can:setting.view')
        ->name('settings.index');

    Route::put('settings', [SettingController::class, 'update'])
        ->middleware('can:setting.update')
        ->name('settings.update');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:user.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
    });

    Route::middleware('can:user.create')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('can:user.update')->group(function () {
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:user.delete')
        ->name('users.destroy');

    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('can:user.view')
        ->name('users.show');

    /*
    |--------------------------------------------------------------------------
    | Audit Logs (Admin only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:Admin')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // NEW — server-side DataTable feed.
        Route::get('audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');

        Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});
