<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Konstruktor dengan middleware auth
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Menampilkan halaman profil user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Mengambil data user yang sedang login dengan eager loading role
        $user = Auth::user()->load('role');

        return view('profile.index', compact('user'));
    }

    /**
     * Menampilkan form ubah password
     *
     * @return \Illuminate\View\View
     */
    public function editPassword()
    {
        // Logging untuk audit trail
        Log::info('User mengakses halaman ubah password', [
            'user_id' => Auth::id(),
            'username' => Auth::user()->username
        ]);

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
                ->uncompromised() // Tambahan untuk mencegah password yang pernah terkena data breach
            ],
        ], [
            'current_password.current_password' => 'Password lama tidak sesuai',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Update password dengan praktik keamanan yang baik
        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        // Logging untuk audit trail (tidak menyimpan password dalam log)
        Log::info('User berhasil mengubah password', [
            'user_id' => $user->id,
            'username' => $user->username,
            'timestamp' => now()
        ]);

        // Invalidate sessions lain untuk keamanan tambahan
        Auth::logoutOtherDevices($request->current_password);

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

        // Validasi input dengan sanitasi
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        // Log perubahan untuk audit trail
        $changes = [];
        if ($user->name !== $validatedData['name']) {
            $changes['name'] = [
                'old' => $user->name,
                'new' => $validatedData['name']
            ];
        }

        if ($user->email !== $validatedData['email']) {
            $changes['email'] = [
                'old' => $user->email,
                'new' => $validatedData['email']
            ];
        }

        // Update profil dengan data yang sudah divalidasi
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->save();

        // Logging untuk audit trail
        if (!empty($changes)) {
            Log::info('User memperbarui profil', [
                'user_id' => $user->id,
                'username' => $user->username,
                'changes' => $changes,
                'timestamp' => now()
            ]);
        }

        return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui');
    }
}
