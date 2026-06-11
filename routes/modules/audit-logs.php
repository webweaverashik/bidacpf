<?php

use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit Log Routes (Admin only)
|--------------------------------------------------------------------------
|
| Read-only audit-trail browser backed by Spatie Activity Log: index page,
| server-side DataTable feed, and a per-entry detail page. Restricted to
| the Admin role (auth + isLoggedIn are applied by the loader).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the `audit-logs/data` feed is declared before the
| `audit-logs/{log}` wildcard show route.
*/

Route::middleware('role:Admin')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Server-side DataTable feed (must precede audit-logs/{log}).
    Route::get('audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');

    Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
});
