<?php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Constructor untuk menerapkan middleware.
     */
    public function __construct()
    {
        // Middleware akan diterapkan di routes
    }

    /**
     * Menampilkan daftar pengguna.
     * 
     * Modifikasi: Menampilkan status API login dan data user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::with('role')
                    ->orderBy('name')
                    ->orderBy('username')
                    ->paginate(10);
        
        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan form untuk membuat pengguna baru.
     * 
     * Form sederhana: hanya username, initial password, dan role
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Menyimpan pengguna baru ke database.
     * 
     * Modifikasi: Simplified registration dengan validasi minimal
     * Admin hanya input: username, initial password, role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validasi input minimal
        $validated = $request->validate([
            'username' => [
                'required', 
                'string', 
                'max:255', 
                'unique:users,username',
                'regex:/^[a-zA-Z0-9._-]+$/'  // Username format validation
            ],
            'initial_password' => [
                'required', 
                'string', 
                'min:6'  // Initial password minimal 6 karakter
            ],
            'role_id' => 'required|exists:roles,id',
        ], [
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, titik, underscore, dan dash.',
            'username.unique' => 'Username sudah terdaftar dalam sistem.',
            'initial_password.min' => 'Password cadangan minimal 6 karakter.',
        ]);

        // Create user dengan data minimal
        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['initial_password']), // PASSWORD BACKUP - TIDAK AKAN DIUBAH API
            'role_id' => $validated['role_id'],
            'name' => null,  // Akan diisi dari API saat first login
            'email' => null, // Akan diisi dari API jika tersedia
            'api_user_id' => null, // Akan diisi saat first login via API
            'is_active' => true,
        ]);

        // Log user creation
        Log::info('New user created with backup password', [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->name ?? 'unknown',
            'has_backup_password' => true,
        ]);

        return redirect()->route('users.index')
                        ->with('success', "User {$user->username} berhasil didaftarkan");
    }

    /**
     * Menampilkan form untuk mengedit pengguna.
     * 
     * Modifikasi: Form edit dengan informasi API status
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Memperbarui pengguna di database.
     * 
     * Modifikasi: Update dengan proteksi data dari API
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        // Validasi input
        $validated = $request->validate([
            'username' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('users')->ignore($user->id),
                'regex:/^[a-zA-Z0-9._-]+$/'
            ],
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'reset_backup_password' => 'nullable|string|min:6', // Password cadangan baru
        ], [
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, titik, underscore, dan dash.',
            'reset_backup_password.min' => 'Password cadangan minimal 6 karakter.',
        ]);

        // Prepare update data
        $userData = [
            'username' => $validated['username'],
            'role_id' => $validated['role_id'],
            'is_active' => $request->has('is_active') ? true : false,
        ];

        // Reset backup password jika diminta (BUKAN dari API)
        $passwordUpdated = false;
        if (!empty($validated['reset_backup_password'])) {
            $userData['password'] = Hash::make($validated['reset_backup_password']);
            $passwordUpdated = true;
            
            Log::info('Backup password manually reset by admin', [
                'user_id' => $user->id,
                'username' => $user->username,
                'admin_user' => Auth::user()->username,
            ]);
        }

        // Update user (TIDAK MENGUBAH DATA DARI API)
        $user->update($userData);

        $message = "Data pengguna '{$user->username}' berhasil diperbarui!";
        
        if ($passwordUpdated) {
            $message .= "<br><strong>Password cadangan telah direset.</strong>";
        }
        
        //$message .= "<br><em>Catatan: Data nama, email, jabatan, dll akan terupdate otomatis dari API KSP saat user login.</em>";

        return redirect()->route('users.index')->with('success', $message);
    }

    /**
     * Reset backup password user (untuk failover mechanism)
     * 
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetBackupPassword(User $user)
    {
        // Generate password cadangan yang kuat
        $backupPassword = 'backup' . str_pad($user->id, 3, '0', STR_PAD_LEFT) . date('dm');
        
        $success = $user->updateBackupPassword($backupPassword);
        
        if ($success) {
            Log::info('Backup password reset by admin', [
                'user_id' => $user->id,
                'username' => $user->username,
                'admin_user' => Auth::user()->username,
            ]);
            
            return redirect()->route('users.index')
                            ->with('success', 
                                   "Password cadangan user '{$user->username}' telah direset.<br>" .
                                   "<strong>Password baru: {$backupPassword}</strong><br>" .
                                   "<em>Password ini dapat digunakan jika server KSP tidak dapat diakses.</em>");
        } else {
            return redirect()->route('users.index')
                            ->with('error', "Gagal mereset password cadangan untuk user '{$user->username}'.");
        }
    }
    
    /**
     * Menghapus pengguna dari database.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Mencegah pengguna menghapus dirinya sendiri
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                           ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        }

        $username = $user->username;
        $user->delete();

        return redirect()->route('users.index')
                        ->with('success', "Pengguna {$username} berhasil dihapus!");
    }

    /**
     * Reset password user ke initial password.
     * 
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(User $user)
    {
        // Generate initial password baru
        $initialPassword = 'password123'; // Atau generate random
        
        $user->update([
            'password' => Hash::make($initialPassword)
        ]);

        return redirect()->route('users.index')
                        ->with('success', 
                               "Password user '{$user->username}' telah direset. " .
                               "Password sementara: {$initialPassword}");
    }

    /**
     * Toggle status aktif user.
     * 
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return redirect()->route('users.index')
                        ->with('success', "User '{$user->username}' berhasil {$status}!");
    }
}