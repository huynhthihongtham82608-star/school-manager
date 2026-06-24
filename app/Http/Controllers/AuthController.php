<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.login')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Mon, 01 Jan 1990 00:00:00 GMT',
            ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => 1,
        ])) {
            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors(['username' => 'Sai tài khoản hoặc mật khẩu'])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Mon, 01 Jan 1990 00:00:00 GMT',
                'Clear-Site-Data' => '"cache", "storage"',
            ]);
    }
}
