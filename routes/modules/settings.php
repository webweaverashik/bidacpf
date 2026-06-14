<?php

use App\Http\Controllers\Employee\EmployeeUploadController;
use App\Http\Controllers\Setting\BackupController;
use App\Http\Controllers\Setting\PayScaleController;
use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| CPF configuration page (contribution rates, advance limits, interest
| rates, installment config). View gated by setting.view, save by
| setting.update.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
*/

Route::get('settings', [SettingController::class, 'index'])
    ->middleware('can:setting.view')
    ->name('settings.index');

Route::put('settings', [SettingController::class, 'update'])
    ->middleware('can:setting.update')
    ->name('settings.update');

/*
|--------------------------------------------------------------------------
| Pay Scale Management (Admin only)
|--------------------------------------------------------------------------
|
| Creation is bulk-upload only (meta form + grade/step grid → preview →
| commit). Pay scales are immutable after creation and are never deleted —
| only activated/deactivated, with at most one active at a time.
|
| Ordering: fixed-segment routes (upload / preview / template / "/") are
| declared before the {payScale} wildcard, and the nested toggle route keeps
| its own /toggle-active segment.
*/
Route::middleware('role:Admin')->prefix('settings/payscale')->name('payscale.')->group(function () {
    Route::get('/', [PayScaleController::class, 'index'])->name('index');
    Route::get('upload', [PayScaleController::class, 'create'])->name('create');
    Route::get('template', [PayScaleController::class, 'template'])->name('template');
    Route::post('preview', [PayScaleController::class, 'preview'])->name('preview');
    Route::post('/', [PayScaleController::class, 'store'])->name('store');

    Route::get('{payScale}', [PayScaleController::class, 'show'])->name('show');
    Route::post('{payScale}/toggle-active', [PayScaleController::class, 'toggleActive'])->name('toggle');
});

/*
|--------------------------------------------------------------------------
| Employee Bulk Upload (employee.create)
|--------------------------------------------------------------------------
|
| AJAX upload → preview/validate → confirm/commit. Surfaced via the Settings
| hero "Employee Upload" tab. Gated by employee.create so Admin and CPF
| Officer can both use it (the hero only shows the link in the local env).
*/
Route::middleware('can:employee.create')->prefix('settings/employee-upload')->name('employee-upload.')->group(function () {
    Route::get('/', [EmployeeUploadController::class, 'index'])->name('index');
    Route::get('template', [EmployeeUploadController::class, 'template'])->name('template');
    Route::post('preview', [EmployeeUploadController::class, 'preview'])->name('preview');
    Route::post('commit', [EmployeeUploadController::class, 'commit'])->name('commit');
});

Route::middleware('role:Admin')->prefix('settings')->group(function () {
    // Backup Routes
    Route::get('backup', [BackupController::class, 'index'])->name('backup');
    Route::get('backup/files', [BackupController::class, 'getBackupFiles'])->name('backup.files');
    Route::post('backup/create', [BackupController::class, 'create'])->name('backup.create');
    Route::get('backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
});
