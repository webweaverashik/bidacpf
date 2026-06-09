<?php

use App\Http\Middleware\IsLoggedIn;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\UnauthorizedException;
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

        /*
        | Unauthorized access → redirect authenticated users to /dashboard.
        | Covers can: (Gate) and Spatie permission:/role: middleware.
        | AJAX/JSON callers get a 403 JSON so the front-end can handle it.
        */
        $redirectUnauthorized = function (Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action.',
                ], 403);
            }

            // Guests fall through to the default (auth) handling → login.
            if (! $request->user()) {
                return null;
            }

            // Defensive: never loop on the dashboard itself.
            if ($request->routeIs('dashboard')) {
                abort(403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'You are not authorized to access that page.');
        };

        // Spatie permission: / role: middleware
        $exceptions->render(function (UnauthorizedException $e, Request $request) use ($redirectUnauthorized) {
            return $redirectUnauthorized($request);
        });

        // Laravel can: middleware / Gate::authorize()
        $exceptions->render(function (AuthorizationException $e, Request $request) use ($redirectUnauthorized) {
            return $redirectUnauthorized($request);
        });
    })
    ->create();
