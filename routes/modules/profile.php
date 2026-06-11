<?php

use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
|
| The signed-in user's own profile: view/update details, change password,
| and read their activity / login-activity feeds. No permission gate — any
| authenticated user may manage their own profile (auth + isLoggedIn are
| applied by the loader).
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
*/

Route::get('profile', [ProfileController::class, 'profile'])->name('users.profile');
Route::post('profile', [ProfileController::class, 'updateProfile'])->name('users.profile.update');
Route::put('profile/password', [ProfileController::class, 'resetPassword'])->name('users.password.reset');
Route::get('profile/activities', [ProfileController::class, 'activities'])->name('users.profile.activities');
Route::get('profile/login-activities', [ProfileController::class, 'loginActivities'])->name('users.profile.login-activities');
