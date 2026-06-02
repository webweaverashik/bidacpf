<?php

use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Password reset, forgot password (guest routes)
| Loaded in bootstrap/app.php with 'web' middleware only
*/

Route::controller(PasswordController::class)
    ->middleware('guest')
    ->group(function () {
        // Forgot Password
        Route::get('forgot-password', 'showLinkRequestForm')->name('password.request');
        Route::post('forgot-password', 'sendResetLinkEmail')->name('password.email');

        // Reset Password redirect
        Route::get('reset-password', fn() => redirect()->route('password.request'))->name('password.reset.request');

        // Reset Password Form & Action
        Route::get('reset-password/{token}', 'showResetForm')->name('password.reset');
        Route::post('reset-password', 'reset')->name('password.update');
    });