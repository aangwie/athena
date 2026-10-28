<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi menggunakan NIDN.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nidn' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.max' => 'NIDN tidak boleh lebih dari 20 karakter.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials['nidn'] = trim($credentials['nidn']);

        $throttleKey = Str::lower($credentials['nidn']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'nidn' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $authenticated = Auth::attempt([
            'nidn' => $credentials['nidn'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'nidn' => 'NIDN atau password yang Anda masukkan tidak sesuai.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Keluar dari aplikasi.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari sistem.');
    }
}
