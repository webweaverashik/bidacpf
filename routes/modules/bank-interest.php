<?php

use App\Http\Controllers\Interest\BankInterestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bank Interest Routes
|--------------------------------------------------------------------------
|
| Bi-annual bank-interest distribution (30 Jun / 31 Dec): create a draft
| batch (auto-computes the per-member preview), review/regenerate, then run
| the submit -> approve / reject -> reverse workflow. Each action is gated by
| its own permission (view, create, submit, approve, reverse).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: list feed, the `distribute` page and all batch actions are
| declared before the `bank-interest/{batch}` wildcard show route.
*/

// ---- Listing + server-side feed + export --------------------------------
Route::middleware('can:bank_interest.view')->group(function () {
    Route::get('bank-interest', [BankInterestController::class, 'index'])->name('bank-interest.index');
    Route::get('bank-interest/data', [BankInterestController::class, 'data'])->name('bank-interest.data');
    Route::get('bank-interest/export', [BankInterestController::class, 'export'])->name('bank-interest.export');
});

// ---- Create batch (auto-computes the preview distribution) --------------
Route::middleware('can:bank_interest.create')->group(function () {
    Route::get('bank-interest/distribute', [BankInterestController::class, 'distribute'])->name('bank-interest.distribute');
    Route::post('bank-interest', [BankInterestController::class, 'store'])->name('bank-interest.store');
    Route::put('bank-interest/{batch}/regenerate', [BankInterestController::class, 'regenerate'])->name('bank-interest.regenerate');
});

// ---- Workflow: officer submit -------------------------------------------
Route::put('bank-interest/{batch}/submit', [BankInterestController::class, 'submit'])
    ->middleware('can:bank_interest.submit')
    ->name('bank-interest.submit');

// ---- Workflow: admin approve / reject -----------------------------------
Route::middleware('can:bank_interest.approve')->group(function () {
    Route::put('bank-interest/{batch}/approve', [BankInterestController::class, 'approve'])->name('bank-interest.approve');
    Route::put('bank-interest/{batch}/reject', [BankInterestController::class, 'reject'])->name('bank-interest.reject');
});

// ---- Workflow: admin reverse --------------------------------------------
Route::put('bank-interest/{batch}/reverse', [BankInterestController::class, 'reverse'])
    ->middleware('can:bank_interest.reverse')
    ->name('bank-interest.reverse');

// ---- Detail (wildcard — keep LAST among bank-interest GETs) -------------
Route::middleware('can:bank_interest.view')->group(function () {
    Route::get('bank-interest/{batch}/distributions/export', [BankInterestController::class, 'exportDistributions'])->name('bank-interest.distributions.export');
    Route::get('bank-interest/{batch}/distributions', [BankInterestController::class, 'distributions'])->name('bank-interest.distributions');
    Route::get('bank-interest/{batch}', [BankInterestController::class, 'show'])->name('bank-interest.show');
});
