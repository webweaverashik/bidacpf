<?php

use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| CPF configuration page (contribution rates, advance limits, interest
| rates, installment config). View gated by setting.view, save by
| setting.update.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
*/

Route::get('settings', [SettingController::class, 'index'])
    ->middleware('can:setting.view')
    ->name('settings.index');

Route::put('settings', [SettingController::class, 'update'])
    ->middleware('can:setting.update')
    ->name('settings.update');
