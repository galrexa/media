<?php
// app/Http/Middleware/RoleMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles Roles yang diizinkan (editor, verifikator1, verifikator2, admin)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Jika user tidak login, redirect ke halaman login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Admin selalu mendapatkan akses ke semua halaman
        if ($request->user()->isAdmin()) {
            return $next($request);
        }

        // Cek apakah user memiliki salah satu role yang diizinkan
        $hasRole = false;
        foreach ($roles as $role) {
            // Gunakan method hasRole dari User model
            if ($request->user()->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if ($hasRole) {
            return $next($request);
        }

        // Jika tidak memiliki izin, redirect dengan error
        return redirect()->route('home')->with('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
