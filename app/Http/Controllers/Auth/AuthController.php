<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the mobile/unified login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasRole('karyawan')) {
                return $this->redirectUserBasedOnRole($user);
            }

            // If an admin/supervisor/atasan is already logged in, redirect them to backoffice
            return redirect()->to('/admin');
        }

        return view('auth.login');
    }

    /**
     * Handle incoming login request with rate limiting and role restriction.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('login')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $login = trim($request->input('login'));
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginType => $login,
            'password' => $request->input('password'),
        ];

        // Verify user exists and check constraints
        $user = User::where($loginType, $login)->first();
        if ($user && ! $user->is_active) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'login' => 'Akun Anda dinonaktifkan. Hubungi Administrator.',
            ]);
        }

        // Restrict employee portal login to karyawan role only
        if ($user && ! $user->hasRole('karyawan')) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'login' => 'Akses Ditolak',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'login' => 'Identitas atau kata sandi tidak cocok.',
            ]);
        }

        /** @var User $authenticatedUser */
        $authenticatedUser = Auth::user();

        // Safety verification: Ensure authenticated user is a karyawan
        if (! $authenticatedUser->hasRole('karyawan')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'Akses Ditolak',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($authenticatedUser->must_change_password) {
            return redirect()->route('password.first-change')
                ->with('warning', 'Demi keamanan, Anda wajib mengganti kata sandi pada login pertama kali.');
        }

        return $this->redirectUserBasedOnRole($authenticatedUser);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Show the mandatory first password change form.
     */
    public function showFirstPasswordChangeForm(): View
    {
        return view('auth.first-password-change');
    }

    /**
     * Process the mandatory first password change.
     */
    public function updateFirstPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
                'different:current_password',
            ],
        ], [
            'current_password.current_password' => 'Kata sandi lama tidak sesuai.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.letters' => 'Kata sandi baru harus mengandung huruf.',
            'password.numbers' => 'Kata sandi baru harus mengandung angka.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.different' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ]);

        // Record audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PASSWORD_CHANGED',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->redirectUserBasedOnRole($user)
            ->with('success', 'Kata sandi berhasil diperbarui. Selamat datang di sistem!');
    }

    /**
     * Helper to redirect user to appropriate portal based on role.
     */
    protected function redirectUserBasedOnRole(User $user): RedirectResponse
    {
        if ($user->must_change_password) {
            return redirect()->route('password.first-change');
        }

        if ($user->hasRole('karyawan')) {
            return redirect()->route('mobile.home');
        }

        return redirect()->to('/admin');
    }
}
