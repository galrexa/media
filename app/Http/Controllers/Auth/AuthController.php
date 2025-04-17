<?php
// app/Http/Controllers/Auth/AuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Isu; // Tambahkan import model Isu

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
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Mencoba login dengan username
        $credentials = [
            'username' => $request->username,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Ambil tanggal isu terakhir
            $latestIsuDate = Isu::latest('tanggal')->first()->tanggal ?? now();
            
            // Tambahkan ke session untuk ditampilkan di splash screen
            session()->flash('login_success', true);
            session()->flash('latestIsuDate', $latestIsuDate->format('d F Y'));

            // Redirect ke halaman yang sesuai berdasarkan role
            $user = Auth::user();
            if ($user->isAdmin() || $user->isEditor()) {
                return redirect()->intended('dashboard/admin');
            } else {
                return redirect()->intended('/'); // Viewer diarahkan ke halaman beranda
            }
        }

        // Jika login gagal
        return back()->withErrors([
            'username' => 'Username atau password yang diberikan tidak cocok dengan data kami.',
        ])->onlyInput('username');
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