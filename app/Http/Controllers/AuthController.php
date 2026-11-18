<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = mb_strtolower($data['login']);
        $key = $login.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('login');
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('id_number', $data['login'])
            ->first();

        if (! $user || ! $user->is_active) {
            RateLimiter::hit($key, 900);

            if ($user) {
                AuditLog::create([
                    'action' => 'auth.disabled_login_attempt',
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'ip_address' => $request->ip(),
                ]);
            }

            return back()->withErrors(['login' => $user ? 'This account is disabled.' : 'The provided credentials are incorrect.'])->onlyInput('login');
        }

        if (! Auth::attempt(['email' => $user->email, 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 900);

            if (RateLimiter::tooManyAttempts($key, 5)) {
                AuditLog::create([
                    'action' => 'auth.account_locked',
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'ip_address' => $request->ip(),
                ]);
            }

            return back()->withErrors(['login' => 'The provided credentials are incorrect.'])->onlyInput('login');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editPassword(): View
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:12', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your password has been updated.');
    }
}
