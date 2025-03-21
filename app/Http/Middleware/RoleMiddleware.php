<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Jika user tidak login, redirect ke halaman login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Dapatkan role user saat ini
        $userRole = $request->user()->role;
        
        // Cek apakah role user ada dalam daftar role yang diizinkan
        if (in_array($userRole, $roles)) {
            return $next($request);
        }
        
        // Jika tidak memiliki izin, redirect dengan error
        return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}