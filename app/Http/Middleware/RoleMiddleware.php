<?php
// app/Http/Middleware/RoleMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Modified untuk mendukung sistem API authentication:
     * - Tambah pengecekan status aktif user
     * - Enhanced logging untuk security monitoring
     * - Improved error handling dan user feedback
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles Roles yang diizinkan (editor, verifikator1, verifikator2, admin)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Jika user tidak login, redirect ke halaman login
        if (!$request->user()) {
            return redirect()->route('login')
                           ->with('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        }

        $user = $request->user();

        // 2. Cek apakah user masih aktif (untuk sistem API authentication)
        if (!$user->is_active) {
            // Log security event
            Log::warning('Inactive user attempted access', [
                'user_id' => $user->id,
                'username' => $user->username,
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Logout user yang tidak aktif
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                           ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator untuk informasi lebih lanjut.');
        }

        // 3. Admin selalu mendapatkan akses ke semua halaman
        if ($user->isAdmin()) {
            // Log admin access untuk audit trail
            if (config('app.log_admin_access', false)) {
                Log::info('Admin access granted', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'route' => $request->route()->getName(),
                    'ip' => $request->ip(),
                ]);
            }
            
            return $next($request);
        }

        // 4. Cek apakah user memiliki salah satu role yang diizinkan
        $hasRole = false;
        $userRole = $user->getHighestRoleName();
        
        foreach ($roles as $role) {
            // Gunakan method hasRole dari User model
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        // 5. Jika memiliki role yang sesuai, izinkan akses
        if ($hasRole) {
            // Log successful access untuk monitoring (opsional)
            if (config('app.log_user_access', false)) {
                Log::info('Role-based access granted', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'user_role' => $userRole,
                    'required_roles' => $roles,
                    'route' => $request->route()->getName(),
                    'ip' => $request->ip(),
                ]);
            }
            
            return $next($request);
        }

        // 6. Jika tidak memiliki izin, log security event dan redirect
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user->id,
            'username' => $user->username,
            'user_role' => $userRole,
            'required_roles' => $roles,
            'route' => $request->route()->getName(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);

        // Enhanced error message dengan informasi role
        $errorMessage = sprintf(
            'Akses ditolak. Halaman ini memerlukan role: %s. Role Anda saat ini: %s. Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.',
            implode(', ', array_map('ucfirst', $roles)),
            ucfirst($userRole)
        );

        // Return response berdasarkan tipe request
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                'user_role' => $userRole,
                'required_roles' => $roles,
                'status_code' => 403
            ], 403);
        }

        return redirect()->route('home')->with('error', $errorMessage);
    }

    /**
     * Helper method untuk mengecek apakah user memiliki minimal salah satu role
     * 
     * @param mixed $user
     * @param array $roles
     * @return bool
     */
    private function userHasAnyRole($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method untuk format role names untuk display
     * 
     * @param array $roles
     * @return string
     */
    private function formatRoleNames(array $roles): string
    {
        $formattedRoles = array_map('ucfirst', $roles);
        
        if (count($formattedRoles) === 1) {
            return $formattedRoles[0];
        }
        
        $lastRole = array_pop($formattedRoles);
        return implode(', ', $formattedRoles) . ' atau ' . $lastRole;
    }
}