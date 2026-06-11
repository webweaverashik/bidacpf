<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
|
| System-user administration: listing + server-side DataTable feed, the
| edit-modal JSON and activity/login-activity feeds, create, update,
| password reset, activate/deactivate, soft-delete and recover. Gated by
| the user.{view|create|update|delete} permissions.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the `users/data`, JSON and activity feeds are declared before
| the `users/{user}` wildcard show route so it does not capture them.
*/

// ---- View: listing, feeds, detail ---------------------------------------
Route::middleware('can:user.view')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');

    // Server-side DataTable feed (must precede users/{user}).
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');

    // Edit-modal JSON + activity feeds.
    Route::get('users/{user}/json', [UserController::class, 'json'])->name('users.json');
    Route::get('users/{user}/activities', [UserController::class, 'activities'])->name('users.activities');
    Route::get('users/{user}/login-activities', [UserController::class, 'loginActivities'])->name('users.login-activities');

    // Full activity page (wildcard — keep LAST among GETs).
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
});

// ---- Create --------------------------------------------------------------
Route::middleware('can:user.create')->group(function () {
    Route::post('users', [UserController::class, 'store'])->name('users.store');
});

// ---- Update / toggle / password -----------------------------------------
Route::middleware('can:user.update')->group(function () {
    Route::post('users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
});

// ---- Delete / recover ----------------------------------------------------
Route::middleware('can:user.delete')->group(function () {
    Route::post('users/recover', [UserController::class, 'recover'])->name('users.recover');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
