<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ScheduledTaskLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit Log + Scheduled Task Log Routes (Admin only)
|--------------------------------------------------------------------------
|
| Read-only log browsers backed by Spatie Activity Log (audit trail) and the
| scheduled_task_logs table (cron run history). Both are index + server-side
| DataTable feed; restricted to the Admin role (auth + isLoggedIn applied by
| the loader).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: each `.../data` feed is declared before its `.../{param}` wildcard
| show route so the wildcard does not swallow the fixed path.
*/

Route::middleware('role:Admin')->group(function () {
    // ── Audit trail ───────────────────────────────────────────────────────
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
    Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // ── Scheduled (cron) task run history ──────────────────────────────────
    Route::get('scheduled-tasks', [ScheduledTaskLogController::class, 'index'])->name('scheduled-tasks.index');
    Route::get('scheduled-tasks/data', [ScheduledTaskLogController::class, 'data'])->name('scheduled-tasks.data');
    Route::get('scheduled-tasks/{taskLog}', [ScheduledTaskLogController::class, 'show'])->name('scheduled-tasks.show');
});
