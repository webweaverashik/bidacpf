<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notification Centre Routes
|--------------------------------------------------------------------------
|
| Per-user notification listing: an index shell + server-side DataTable feed,
| plus "open" (mark read + forward), "mark all read", and delete. Every route
| is scoped to the signed-in user's own notifications inside the controller,
| so no permission gate is applied here — auth + isLoggedIn come from the
| module loader.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the fixed `data` / `read-all` paths are declared before the
| `{notification}` wildcard routes so the wildcard does not swallow them.
*/

Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
