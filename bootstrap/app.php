<?php

use App\Http\Middleware\IsLoggedIn;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Guest auth routes (forgot/reset password)
            Route::middleware('web')->group(base_path('routes/auth.php'));

            // CPF application module routes (employees, contributions, ledger, etc.)
            Route::middleware('web')->group(base_path('routes/modules.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isLoggedIn'         => IsLoggedIn::class,

            // Spatie Permission middleware
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'message' => 'Your session has expired. Please login again.',
                    ],
                    419,
                );
            }

            return redirect()->route('login')->with('error', 'Your session has expired due to inactivity. Please login again.');
        });
    })
    ->create();
