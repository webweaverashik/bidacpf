<?php

use App\Http\Controllers\Cpf\CpfAdvanceController;
use App\Http\Controllers\Cpf\CpfAdvanceRecoveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CPF Advance & Recovery Routes
|--------------------------------------------------------------------------
|
| Advances (CpfAdvanceController) and recovery postings
| (CpfAdvanceRecoveryController) share the `cpf-advances/...` URL prefix, so
| they live together here. Route ORDER is significant and must not change:
|
|   - Fixed paths (create, eligibility, outstanding, recovery) come before
|     the `{advance}` and `{recovery}` wildcards.
|   - Recovery detail is declared before the advance detail wildcard so
|     `cpf-advances/{advance}/recovery/{recovery}` is matched correctly.
|   - The bare `cpf-advances/{advance}` show route is the LAST GET.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
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
