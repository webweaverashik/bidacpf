<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Core Entry Point)
|--------------------------------------------------------------------------
|
| Kept deliberately minimal. Only the public auth entry, the dashboard and
| a few core/session routes live here. Everything else is split out and
| loaded from bootstrap/app.php via the `then:` closure:
|
| routes/
| ├── web.php          # This file — public + core authenticated routes
| ├── auth.php         # Guest auth routes (forgot / reset password)
| ├── modules.php      # Loader for the application modules below
| └── modules/         # One file per feature module
|     ├── employees.php
|     ├── employee-salary.php
|     ├── cpf-contributions.php
|     ├── cpf-ledger.php
|     ├── cpf-advances.php
|     ├── bank-interest.php
|     ├── reports.php
|     ├── settings.php
|     ├── users.php
|     ├── profile.php
|     └── audit-logs.php
*/

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Login screen and authentication submit. Reachable without a session.
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Core Routes
|--------------------------------------------------------------------------
| Dashboard, logout and the cache-clear helper. Guarded by `auth` and the
| single-session `isLoggedIn` middleware.
*/
Route::middleware(['auth', 'isLoggedIn'])->group(function () {
    // Dashboard main view.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Logout (POST performs logout; GET bounces back — used by stray links).
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', fn() => redirect()->back())->name('logout.get');

    // Clear application/server cache (AJAX helper).
    Route::get('clear-cache', function () {
        clearServerCache();
        return response()->json(['success' => true]);
    })->name('clear.cache');
});

/*
|--------------------------------------------------------------------------
| Guest Logout Fallback
|--------------------------------------------------------------------------
| Handles GET /logout when there is no active session — send guests to the
| login screen instead of bouncing back.
*/
Route::get('/logout', fn() => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Mail Test (DEV ONLY)
|--------------------------------------------------------------------------
| Quick mailer smoke-test. REMOVE before deploying to production.
*/
Route::get('/send-test-email', function () {
    Mail::raw('This is a test email!', function ($message) {
        $message->to('test@example.com')->subject('Test Email');
    });
    return 'Test email sent!';
});
