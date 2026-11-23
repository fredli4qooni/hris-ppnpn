<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply production-grade security headers (CSIRT Kemenkeu & UU PDP).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        // Content-Security-Policy tailored for Laravel Vite, Tailwind CDN, Leaflet, and Face-API
        $csp = "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com; "
            ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; "
            ."font-src 'self' https://fonts.gstatic.com data:; "
            ."img-src 'self' data: blob: https:; "
            ."connect-src 'self' https: data: blob:; "
            ."media-src 'self' blob:; "
            ."frame-ancestors 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
