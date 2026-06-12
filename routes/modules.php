<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Module Routes (Loader)
|--------------------------------------------------------------------------
|
| This file is loaded from bootstrap/app.php under the "web" middleware
| group. Every route across the modules below sits behind authentication
| and the single-session "isLoggedIn" guard, so that shared middleware is
| applied here ONCE and each module file is required inside the group.
|
| Because the files are required within the group closure, the routes they
| register inherit the ['auth', 'isLoggedIn'] middleware automatically — the
| individual module files therefore do NOT repeat it.
|
| Adding a module:
|   1. Drop a new file in routes/modules/.
|   2. Add a require line to the ordered list below.
|
| Ordering notes:
|   - Cross-module order only matters when two modules share a URL prefix.
|   - Within a module, fixed-segment routes MUST be declared before wildcard
|     ({param}) routes so the wildcard does not swallow the fixed paths.
|
| routes/
| ├── web.php                  # Core / public entry point (minimal)
| ├── auth.php                 # Guest auth routes (forgot / reset password)
| ├── modules.php              # This loader
| └── modules/
|     ├── employees.php
|     ├── employee-salary.php
|     ├── cpf-contributions.php
|     ├── cpf-ledger.php
|     ├── cpf-advances.php      # Advances + recoveries (shared prefix)
|     ├── bank-interest.php
|     ├── reports.php
|     ├── settings.php
|     ├── users.php
|     ├── profile.php
|     └── audit-logs.php
*/

Route::middleware(['auth', 'isLoggedIn'])->group(function () {
    require __DIR__ . '/modules/employees.php';
    require __DIR__ . '/modules/employee-salary.php';
    require __DIR__ . '/modules/cpf-contributions.php';
    require __DIR__ . '/modules/cpf-ledger.php';
    require __DIR__ . '/modules/cpf-advances.php';
    require __DIR__ . '/modules/cpf-settlements.php';   // ← add this
    require __DIR__ . '/modules/bank-interest.php';
    require __DIR__ . '/modules/reports.php';
    require __DIR__ . '/modules/settings.php';
    require __DIR__ . '/modules/users.php';
    require __DIR__ . '/modules/profile.php';
    require __DIR__ . '/modules/audit-logs.php';
});
