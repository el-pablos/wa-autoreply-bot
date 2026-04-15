<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('authenticated')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $correct = $request->password === config('app.dashboard_password');

        if (!$correct) {
            return back()->withErrors(['password' => 'Password salah.'])->withInput();
        }

        session(['authenticated' => true]);
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('authenticated');
        return redirect()->route('login');
    }
}
