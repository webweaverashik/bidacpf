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
use App\Http\Controllers\User\ProfileController;
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
    Route::get('cpf-contributions', [CpfContributionController::class, 'index'])
        ->middleware('can:cpf_contribution.view')
        ->name('cpf-contributions.index');

    Route::post('cpf-contributions', [CpfContributionController::class, 'store'])
        ->middleware('can:cpf_contribution.create')
        ->name('cpf-contributions.store');

    Route::put('cpf-contributions/{batch}/regenerate', [CpfContributionController::class, 'regenerate'])
        ->middleware('can:cpf_contribution.create')
        ->name('cpf-contributions.regenerate');

    Route::patch('cpf-contributions/{batch}/contributions/{contribution}', [CpfContributionController::class, 'updateContribution'])
        ->middleware('can:cpf_contribution.create')
        ->name('cpf-contributions.contributions.update');

    Route::put('cpf-contributions/{batch}/submit', [CpfContributionController::class, 'submit'])
        ->middleware('can:cpf_contribution.submit')
        ->name('cpf-contributions.submit');

    Route::put('cpf-contributions/{batch}/approve', [CpfContributionController::class, 'approve'])
        ->middleware('can:cpf_contribution.approve')
        ->name('cpf-contributions.approve');

    Route::put('cpf-contributions/{batch}/reject', [CpfContributionController::class, 'reject'])
        ->middleware('can:cpf_contribution.approve')
        ->name('cpf-contributions.reject');

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
        // Members
        Route::get('cpf-ledger', [CpfLedgerController::class, 'index'])->name('cpf-ledger.index');
        Route::get('cpf-ledger/data', [CpfLedgerController::class, 'indexData'])->name('cpf-ledger.data');
        Route::get('cpf-ledger/export', [CpfLedgerController::class, 'export'])->name('cpf-ledger.export');

        // Transactions
        Route::get('cpf-ledger/transactions', [CpfLedgerController::class, 'transactions'])->name('cpf-ledger.transactions');
        Route::get('cpf-ledger/transactions/data', [CpfLedgerController::class, 'transactionsData'])->name('cpf-ledger.transactions.data');
        Route::get('cpf-ledger/transactions/export', [CpfLedgerController::class, 'transactionsExport'])->name('cpf-ledger.transactions.export');

        // Statement (per employee) — wildcard registered LAST
        Route::get('cpf-ledger/{employee}', [CpfLedgerController::class, 'show'])->name('cpf-ledger.show');
        Route::get('cpf-ledger/{employee}/data', [CpfLedgerController::class, 'statementData'])->name('cpf-ledger.statement.data');
        Route::get('cpf-ledger/{employee}/export', [CpfLedgerController::class, 'statementExport'])->name('cpf-ledger.statement.export');
    });

    /*
    |--------------------------------------------------------------------------
    | CPF Advances
    |--------------------------------------------------------------------------
    */
    // ---- Read-only pages + server-side feeds + exports ----------------------
    Route::middleware('can:cpf_advance.view')->group(function () {
        // Advance applications
        Route::get('cpf-advances', [CpfAdvanceController::class, 'index'])->name('cpf-advances.index');
        Route::get('cpf-advances/data', [CpfAdvanceController::class, 'data'])->name('cpf-advances.data');
        Route::get('cpf-advances/export', [CpfAdvanceController::class, 'export'])->name('cpf-advances.export');

        // Outstanding advances
        Route::get('cpf-advances/outstanding', [CpfAdvanceController::class, 'outstanding'])->name('cpf-advances.outstanding');
        Route::get('cpf-advances/outstanding/data', [CpfAdvanceController::class, 'outstandingData'])->name('cpf-advances.outstanding.data');
        Route::get('cpf-advances/outstanding/export', [CpfAdvanceController::class, 'outstandingExport'])->name('cpf-advances.outstanding.export');

        // Recovery postings
        Route::get('cpf-advances/recovery', [CpfAdvanceRecoveryController::class, 'index'])->name('cpf-advances.recovery.index');
        Route::get('cpf-advances/recovery/data', [CpfAdvanceRecoveryController::class, 'data'])->name('cpf-advances.recovery.data');
        Route::get('cpf-advances/recovery/export', [CpfAdvanceRecoveryController::class, 'export'])->name('cpf-advances.recovery.export');
    });

    // ---- Officer: draft create + AJAX eligibility ---------------------------
    Route::middleware('can:cpf_advance.create')->group(function () {
        Route::get('cpf-advances/create', [CpfAdvanceController::class, 'create'])->name('cpf-advances.create');
        Route::post('cpf-advances', [CpfAdvanceController::class, 'store'])->name('cpf-advances.store');

        Route::get('cpf-advances/eligibility/{employee}', [CpfAdvanceController::class, 'eligibility'])
            ->name('cpf-advances.eligibility');

        Route::get('cpf-advances/{advance}/edit', [CpfAdvanceController::class, 'edit'])->name('cpf-advances.edit');
        Route::put('cpf-advances/{advance}', [CpfAdvanceController::class, 'update'])->name('cpf-advances.update');
        Route::delete('cpf-advances/{advance}', [CpfAdvanceController::class, 'destroy'])->name('cpf-advances.destroy');
    });

    // ---- Officer: submit draft for approval ---------------------------------
    Route::put('cpf-advances/{advance}/submit', [CpfAdvanceController::class, 'submit'])
        ->middleware('can:cpf_advance.submit')
        ->name('cpf-advances.submit');

    // ---- Admin: approve / reject / reschedule -------------------------------
    Route::middleware('can:cpf_advance.approve')->group(function () {
        Route::put('cpf-advances/{advance}/approve', [CpfAdvanceController::class, 'approve'])->name('cpf-advances.approve');
        Route::put('cpf-advances/{advance}/reject', [CpfAdvanceController::class, 'reject'])->name('cpf-advances.reject');
        Route::put('cpf-advances/{advance}/reschedule', [CpfAdvanceController::class, 'reschedule'])->name('cpf-advances.reschedule');
    });

    // ---- Recovery: officer draft/submit/edit/delete -------------------------
    Route::middleware('can:cpf_advance.recovery')->group(function () {
        Route::get('cpf-advances/{advance}/recovery/create', [CpfAdvanceRecoveryController::class, 'create'])->name('cpf-advances.recovery.create');
        Route::post('cpf-advances/{advance}/recovery', [CpfAdvanceRecoveryController::class, 'store'])->name('cpf-advances.recovery.store');
        Route::get('cpf-advances/{advance}/recovery/{recovery}/edit', [CpfAdvanceRecoveryController::class, 'edit'])->name('cpf-advances.recovery.edit');
        Route::put('cpf-advances/{advance}/recovery/{recovery}', [CpfAdvanceRecoveryController::class, 'update'])->name('cpf-advances.recovery.update');
        Route::delete('cpf-advances/{advance}/recovery/{recovery}', [CpfAdvanceRecoveryController::class, 'destroy'])->name('cpf-advances.recovery.destroy');
    });

    // ---- Recovery: officer submit -------------------------------------------
    Route::put('cpf-advances/{advance}/recovery/{recovery}/submit', [CpfAdvanceRecoveryController::class, 'submit'])
        ->middleware('can:cpf_advance.submit')
        ->name('cpf-advances.recovery.submit');

    // ---- Recovery: admin approve / reject -----------------------------------
    Route::middleware('can:cpf_advance.approve')->group(function () {
        Route::put('cpf-advances/{advance}/recovery/{recovery}/approve', [CpfAdvanceRecoveryController::class, 'approve'])->name('cpf-advances.recovery.approve');
        Route::put('cpf-advances/{advance}/recovery/{recovery}/reject', [CpfAdvanceRecoveryController::class, 'reject'])->name('cpf-advances.recovery.reject');
    });

    // ---- Recovery detail (after recovery/create to avoid {recovery} clash) ---
    Route::get('cpf-advances/{advance}/recovery/{recovery}', [CpfAdvanceRecoveryController::class, 'show'])
        ->middleware('can:cpf_advance.view')
        ->name('cpf-advances.recovery.show');

    // ---- Advance detail (wildcard — keep LAST among cpf-advances GETs) -------
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

        // Server-side DataTable feed (must precede users/{user}).
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');

        // Edit-modal JSON + activity feeds.
        Route::get('users/{user}/json', [UserController::class, 'json'])->name('users.json');
        Route::get('users/{user}/activities', [UserController::class, 'activities'])->name('users.activities');
        Route::get('users/{user}/login-activities', [UserController::class, 'loginActivities'])->name('users.login-activities');

        // Full activity page (wildcard — keep LAST among GETs).
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('can:user.create')->group(function () {
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('can:user.update')->group(function () {
        Route::post('users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    });

    Route::middleware('can:user.delete')->group(function () {
        Route::post('users/recover', [UserController::class, 'recover'])->name('users.recover');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('profile', [ProfileController::class, 'profile'])->name('users.profile');
    Route::post('profile', [ProfileController::class, 'updateProfile'])->name('users.profile.update');
    Route::put('profile/password', [ProfileController::class, 'resetPassword'])->name('users.password.reset');
    Route::get('profile/activities', [ProfileController::class, 'activities'])->name('users.profile.activities');
    Route::get('profile/login-activities', [ProfileController::class, 'loginActivities'])->name('users.profile.login-activities');

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
