<?php

use App\Http\Controllers\Setting\BackupController;
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

Route::middleware('role:Admin')->prefix('settings')->group(function () {
    // Backup Routes
    Route::get('backup', [BackupController::class, 'index'])->name('backup');
    Route::get('backup/files', [BackupController::class, 'getBackupFiles'])->name('backup.files');
    Route::post('backup/create', [BackupController::class, 'create'])->name('backup.create');
    Route::get('backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
});
