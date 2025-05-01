<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Mengambil data user yang sedang login
        $user = Auth::user();
        
        return view('profile.index', compact('user'));
    }
    
    /**
     * Menampilkan form ubah password
     *
     * @return \Illuminate\View\View
     */
    public function editPassword()
    {
        return view('profile.password');
    }
    
    /**
     * Memproses perubahan password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        // Validasi input dari user
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ], [
            'current_password.current_password' => 'Password lama tidak sesuai',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);
        
        // Update password
        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();
        
        return redirect()->route('profile.index')->with('success', 'Password berhasil diubah');
    }
    
    /**
     * Memperbarui informasi profil
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        
        return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui');
    }
}