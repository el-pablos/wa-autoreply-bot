<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\AuditTrail;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            AuditTrail::record(
                $request,
                'auth.login_failed',
                ['type' => 'user', 'id' => null],
                null,
                ['email' => (string) $request->input('email')],
                'guest:' . (string) $request->input('email')
            );

            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            AuditTrail::record(
                $request,
                'auth.login',
                $user,
                null,
                ['email' => $user->email]
            );
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user) {
            AuditTrail::record(
                $request,
                'auth.logout',
                $user,
                null,
                ['email' => $user->email]
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
