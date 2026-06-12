<?php

use App\Http\Controllers\Cpf\SettlementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CPF Final Settlement Routes
|--------------------------------------------------------------------------
|
| Retirement / resignation / deceased settlements run the officer -> admin
| approval workflow (view, create, submit, approve). On approval the service
| posts the FINAL_SETTLEMENT closing entry, writes off any open advance, and
| transitions the member out of active service.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the list feed, export, create, preview and the {settlement}
| sub-action routes are declared before the bare `cpf-settlements/{settlement}`
| wildcard show route, which is LAST among the GETs.
*/

// ---- Listing + server-side feed + export --------------------------------
Route::middleware('can:cpf_settlement.view')->group(function () {
    Route::get('cpf-settlements', [SettlementController::class, 'index'])->name('cpf-settlements.index');
    Route::get('cpf-settlements/data', [SettlementController::class, 'data'])->name('cpf-settlements.data');
    Route::get('cpf-settlements/export', [SettlementController::class, 'export'])->name('cpf-settlements.export');
});

// ---- Officer: draft create / edit + AJAX preview ------------------------
Route::middleware('can:cpf_settlement.create')->group(function () {
    Route::get('cpf-settlements/create', [SettlementController::class, 'create'])->name('cpf-settlements.create');
    Route::post('cpf-settlements', [SettlementController::class, 'store'])->name('cpf-settlements.store');

    Route::get('cpf-settlements/preview/{employee}', [SettlementController::class, 'preview'])
        ->name('cpf-settlements.preview');

    Route::get('cpf-settlements/{settlement}/edit', [SettlementController::class, 'edit'])->name('cpf-settlements.edit');
    Route::put('cpf-settlements/{settlement}', [SettlementController::class, 'update'])->name('cpf-settlements.update');
    Route::delete('cpf-settlements/{settlement}', [SettlementController::class, 'destroy'])->name('cpf-settlements.destroy');
});

// ---- Officer: submit draft for approval ---------------------------------
Route::put('cpf-settlements/{settlement}/submit', [SettlementController::class, 'submit'])
    ->middleware('can:cpf_settlement.submit')
    ->name('cpf-settlements.submit');

// ---- Admin: approve / reject --------------------------------------------
Route::middleware('can:cpf_settlement.approve')->group(function () {
    Route::put('cpf-settlements/{settlement}/approve', [SettlementController::class, 'approve'])->name('cpf-settlements.approve');
    Route::put('cpf-settlements/{settlement}/reject', [SettlementController::class, 'reject'])->name('cpf-settlements.reject');
});

// ---- Detail (wildcard — keep LAST among cpf-settlements GETs) ------------
Route::get('cpf-settlements/{settlement}', [SettlementController::class, 'show'])
    ->middleware('can:cpf_settlement.view')
    ->name('cpf-settlements.show');
