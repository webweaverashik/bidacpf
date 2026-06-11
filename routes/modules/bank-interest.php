<?php

use App\Http\Controllers\Interest\BankInterestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bank Interest Routes
|--------------------------------------------------------------------------
|
| Yearly bank-interest distribution: build a distribution batch, generate
| the per-member allocations, then submit or reverse it. Gated by the
| bank_interest.{view|create|submit|reverse} permissions.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the `distribute` page and batch actions are declared before the
| `bank-interest/{batch}` wildcard show route.
*/

// ---- Listing -------------------------------------------------------------
Route::middleware('can:bank_interest.view')->group(function () {
    Route::get('bank-interest', [BankInterestController::class, 'index'])->name('bank-interest.index');
});

// ---- Create batch + generate allocations --------------------------------
Route::middleware('can:bank_interest.create')->group(function () {
    Route::get('bank-interest/distribute', [BankInterestController::class, 'distribute'])->name('bank-interest.distribute');
    Route::post('bank-interest', [BankInterestController::class, 'store'])->name('bank-interest.store');
    Route::post('bank-interest/{batch}/generate', [BankInterestController::class, 'generate'])->name('bank-interest.generate');
});

// ---- Workflow: submit ----------------------------------------------------
Route::put('bank-interest/{batch}/submit', [BankInterestController::class, 'submit'])
    ->middleware('can:bank_interest.submit')
    ->name('bank-interest.submit');

// ---- Workflow: reverse ---------------------------------------------------
Route::put('bank-interest/{batch}/reverse', [BankInterestController::class, 'reverse'])
    ->middleware('can:bank_interest.reverse')
    ->name('bank-interest.reverse');

// ---- Detail (wildcard — keep LAST among bank-interest GETs) -------------
Route::get('bank-interest/{batch}', [BankInterestController::class, 'show'])
    ->middleware('can:bank_interest.view')
    ->name('bank-interest.show');
