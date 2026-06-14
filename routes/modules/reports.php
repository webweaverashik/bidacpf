<?php

use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
|
| A single reporting hub. The page (reports.index) lets the user pick a
| report from a grouped, role-filtered dropdown; the parameter panel and the
| on-screen preview load over AJAX; and every report can be downloaded as
| PDF / Excel / CSV.
|
| Access is layered:
|   - report.view   gates the page, the AJAX param + preview feeds.
|   - report.export gates the download endpoint.
|   - Each individual report additionally enforces its own gate via
|     App\Support\ReportRegistry (e.g. the Audit & Login reports stay
|     Admin-only; module reports follow the viewer's module permissions).
|
| The legacy Audit Log browser still lives at routes/modules/audit-logs.php
| (Admin-only, server-side DataTable). These report-module endpoints add the
| downloadable Activity Audit Log + Login Activity reports alongside it, and
| the sidebar now surfaces audit access from inside the Reports group.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: fixed segments (params / preview / generate) are declared before
| any wildcard, though this module has none.
*/

// ---- Page + AJAX feeds ---------------------------------------------------
Route::middleware('can:report.view')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/params', [ReportController::class, 'params'])->name('reports.params');
    Route::get('reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
});

// ---- Download (PDF / Excel / CSV) ---------------------------------------
Route::middleware('can:report.export')->group(function () {
    Route::get('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
});
