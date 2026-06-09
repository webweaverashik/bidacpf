<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
routes/
├── web.php              # Main entry point (minimal)
├── auth.php             # Authentication routes (guest)
├── modules.php          # Module-specific routes
*/

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Minimal entry point - only core routes here
| Other routes are loaded via bootstrap/app.php
*/

// Public routes
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated core routes
Route::middleware(['auth', 'isLoggedIn'])->group(function () {
    // Dashboard main view
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Profile
    Route::get('profile', [ProfileController::class, 'profile'])->name('users.profile');

    // Logout routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', fn() => redirect()->back())->name('logout.get');

    // Cache clearing
    Route::get('clear-cache', function () {
        clearServerCache();
        return response()->json(['success' => true]);
    })->name('clear.cache');
});

// Guest logout redirect (handles /logout when not authenticated)
Route::get('/logout', fn() => redirect()->route('login'));

// Testing mail server (remove in production)
Route::get('/send-test-email', function () {
    Mail::raw('This is a test email!', function ($message) {
        $message->to('test@example.com')->subject('Test Email');
    });
    return 'Test email sent!';
});
