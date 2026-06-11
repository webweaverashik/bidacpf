<?php

use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
|
| On-screen reports (account slip, ledger statement, contribution summary,
| advance summary) gated by report.view, and their matching export
| endpoints gated by report.export.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
*/

// ---- On-screen reports ---------------------------------------------------
Route::middleware('can:report.view')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/account-slip', [ReportController::class, 'accountSlip'])->name('reports.account-slip');
    Route::get('reports/ledger-statement', [ReportController::class, 'ledgerStatement'])->name('reports.ledger-statement');
    Route::get('reports/contribution-summary', [ReportController::class, 'contributionSummary'])->name('reports.contribution-summary');
    Route::get('reports/advance-summary', [ReportController::class, 'advanceSummary'])->name('reports.advance-summary');
});

// ---- Exports -------------------------------------------------------------
Route::middleware('can:report.export')->group(function () {
    Route::get('reports/account-slip/export', [ReportController::class, 'exportAccountSlip'])->name('reports.account-slip.export');
    Route::get('reports/ledger-statement/export', [ReportController::class, 'exportLedgerStatement'])->name('reports.ledger-statement.export');
    Route::get('reports/contribution-summary/export', [ReportController::class, 'exportContributionSummary'])->name('reports.contribution-summary.export');
    Route::get('reports/advance-summary/export', [ReportController::class, 'exportAdvanceSummary'])->name('reports.advance-summary.export');
});
