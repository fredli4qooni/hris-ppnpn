<?php

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'password.changed' => EnsurePasswordIsChanged::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: function () {
                /** @var User|null $user */
                $user = Auth::user();
                if ($user && method_exists($user, 'hasRole') && $user->hasRole('karyawan')) {
                    return $user->must_change_password ? '/password/first-change' : '/mobile/home';
                }

                return '/admin';
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
