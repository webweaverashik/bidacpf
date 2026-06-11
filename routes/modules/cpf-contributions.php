<?php

use App\Http\Controllers\Cpf\CpfContributionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CPF Contribution Routes
|--------------------------------------------------------------------------
|
| Monthly contribution batch lifecycle: create/regenerate, per-row edits,
| and the submit -> approve / reject -> reverse workflow. Each action is
| gated by its own permission (view, create, submit, approve, reverse).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: all batch action routes are declared before the
| `cpf-contributions/{batch}` wildcard show route.
*/

// ---- Listing -------------------------------------------------------------
Route::get('cpf-contributions', [CpfContributionController::class, 'index'])
    ->middleware('can:cpf_contribution.view')
    ->name('cpf-contributions.index');

// ---- Create / regenerate batch ------------------------------------------
Route::post('cpf-contributions', [CpfContributionController::class, 'store'])
    ->middleware('can:cpf_contribution.create')
    ->name('cpf-contributions.store');

Route::put('cpf-contributions/{batch}/regenerate', [CpfContributionController::class, 'regenerate'])
    ->middleware('can:cpf_contribution.create')
    ->name('cpf-contributions.regenerate');

// ---- Per-row edit --------------------------------------------------------
Route::patch('cpf-contributions/{batch}/contributions/{contribution}', [CpfContributionController::class, 'updateContribution'])
    ->middleware('can:cpf_contribution.create')
    ->name('cpf-contributions.contributions.update');

// ---- Workflow: submit ----------------------------------------------------
Route::put('cpf-contributions/{batch}/submit', [CpfContributionController::class, 'submit'])
    ->middleware('can:cpf_contribution.submit')
    ->name('cpf-contributions.submit');

// ---- Workflow: approve / reject -----------------------------------------
Route::put('cpf-contributions/{batch}/approve', [CpfContributionController::class, 'approve'])
    ->middleware('can:cpf_contribution.approve')
    ->name('cpf-contributions.approve');

Route::put('cpf-contributions/{batch}/reject', [CpfContributionController::class, 'reject'])
    ->middleware('can:cpf_contribution.approve')
    ->name('cpf-contributions.reject');

// ---- Workflow: reverse ---------------------------------------------------
Route::put('cpf-contributions/{batch}/reverse', [CpfContributionController::class, 'reverse'])
    ->middleware('can:cpf_contribution.reverse')
    ->name('cpf-contributions.reverse');

// ---- Detail (wildcard — keep LAST among cpf-contributions GETs) ---------
Route::get('cpf-contributions/{batch}', [CpfContributionController::class, 'show'])
    ->middleware('can:cpf_contribution.view')
    ->name('cpf-contributions.show');
