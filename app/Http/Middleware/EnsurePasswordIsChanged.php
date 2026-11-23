<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            $allowedRoutes = [
                'password.first-change',
                'password.first-change.update',
                'logout',
                'filament.admin.auth.logout',
            ];

            if (! in_array($request->route()?->getName(), $allowedRoutes, true)) {
                return redirect()->route('password.first-change')
                    ->with('warning', 'Demi keamanan, Anda wajib mengganti kata sandi pada login pertama kali sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
