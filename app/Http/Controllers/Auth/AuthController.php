<?php
// app/Http/Controllers/Auth/AuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle proses login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validasi request
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Mencoba login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirect ke halaman yang sesuai berdasarkan role
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->intended('dashboard/admin');
            } elseif ($user->isEditor()) {
                return redirect()->intended('dashboard/editor');
            } else {
                return redirect()->intended('dashboard');
            }
        }

        // Jika login gagal
        return back()->withErrors([
            'email' => 'Email atau password yang diberikan tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    /**
     * Handle proses logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}