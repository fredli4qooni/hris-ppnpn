<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(AuthEventSubscriber::class);

        // Rate Limiter for Authentication Attempts (US-703: max 5 requests per minute)
        RateLimiter::for('auth-attempt', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) ($request->input('login') ?: $request->input('username', ''))).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request, array $headers) use ($throttleKey) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak percobaan masuk (login). Silakan tunggu 1 menit sebelum mencoba kembali.',
                    ], 429, $headers);
                }

                $seconds = RateLimiter::availableIn($throttleKey) ?: 60;

                throw ValidationException::withMessages([
                    'login' => "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$seconds} detik.",
                ]);
            });
        });

        // Rate Limiter for Attendance API (US-703: max 30 requests per minute)
        RateLimiter::for('attendance-api', function (Request $request) {
            $userKey = (string) ($request->user()?->id ?: $request->ip());

            return Limit::perMinute(30)->by($userKey)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Frekuensi permintaan presensi melebihi ambang batas (maksimal 30 request per menit). Harap tunggu sejenak.',
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                ], 429);
            });
        });
    }
}
