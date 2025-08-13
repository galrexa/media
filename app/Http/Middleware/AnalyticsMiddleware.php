<?php
// app/Http/Middleware/AnalyticsMiddleware.php - Updated untuk track semua role

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\UserAnalytics;
use Carbon\Carbon;

class AnalyticsMiddleware
{
    /**
     * Handle an incoming request.
     * Updated untuk track SEMUA role, bukan hanya viewer
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Skip debug dan development routes
        if ($this->shouldSkipRoute($request->path())) {
            return $response;
        }
        
        // Log middleware hit untuk debugging
        Log::info('ANALYTICS MIDDLEWARE HIT', [
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'method' => $request->method(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id()
        ]);
        
        // Cek authentication
        if (!Auth::check()) {
            Log::info('SKIP: Not authenticated');
            return $response;
        }
        
        $user = Auth::user();
        
        // Cek user aktif
        if (isset($user->is_active) && !$user->is_active) {
            Log::info('SKIP: User not active', ['user_id' => $user->id]);
            return $response;
        }
        
        // Log user details
        Log::info('USER DETAILS', [
            'id' => $user->id,
            'username' => $user->username,
            'role_id' => $user->role_id,
            'role_name' => $user->getHighestRoleName(),
            'is_active' => $user->is_active
        ]);
        
        // PERUBAHAN UTAMA: Track SEMUA role, bukan hanya viewer
        try {
            $roleName = $user->getHighestRoleName();
            Log::info('ROLE CHECK - ALL ROLES TRACKED', [
                'role_name' => $roleName,
                'will_track' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('ROLE CHECK ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $response;
        }
        
        // Check request conditions
        if (!$request->isMethod('GET')) {
            Log::info('SKIP: Not GET request', ['method' => $request->method()]);
            return $response;
        }
        
        if ($request->ajax() || $request->expectsJson()) {
            Log::info('SKIP: AJAX or JSON request');
            return $response;
        }
        
        // Determine page name
        $pageName = $this->determinePageName($request);
        Log::info('PAGE NAME DETERMINED', [
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'page_name' => $pageName,
            'user_role' => $roleName
        ]);
        
        // Check if should track page (lebih permisif untuk semua role)
        $shouldTrackPage = $this->shouldTrackPage($pageName, $roleName);
        Log::info('SHOULD TRACK PAGE', [
            'page_name' => $pageName,
            'user_role' => $roleName,
            'should_track' => $shouldTrackPage
        ]);
        
        if (!$shouldTrackPage) {
            Log::info('SKIP: Page not trackable for this role');
            return $response;
        }
        
        // TRACK THE PAGE!
        try {
            Log::info('TRACKING PAGE VISIT...', ['user_role' => $roleName]);
            $this->trackPageVisit($request, $user);
            Log::info('PAGE VISIT TRACKED SUCCESSFULLY');
        } catch (\Exception $e) {
            Log::error('TRACKING FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $response;
    }
    
    /**
     * Tentukan apakah route harus di-skip
     */
    private function shouldSkipRoute($path): bool
    {
        $excludePaths = [
            'admin/analytics/chart-data', // Skip API calls saja
            'admin/analytics/real-time',  // Skip API calls saja  
            'admin/analytics/export',     // Skip export
            'api/', 'dev/', 'sanctum/', 'storage/', 'up',
            'assets/', 'css/', 'js/', 'images/', 'favicon.ico',
            'notifications/', 'notifikasi/', 'reset-', 'show-', 'debug/'
            // REMOVE: 'analytics/' - sekarang analytics dashboard akan di-track!
        ];
        
        foreach ($excludePaths as $excludePath) {
            if (str_starts_with($path, $excludePath)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Tentukan nama halaman berdasarkan request
     * Updated untuk support halaman admin/editor
     */
    private function determinePageName(Request $request): string
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();
        
        // Route mapping untuk semua role
        $routeMapping = [
            // Viewer pages
            'home' => 'dashboard_viewer',
            'home.index' => 'dashboard_viewer',
            'dashboard.landing' => 'dashboard_viewer',
            'isu.show' => 'isu_detail',
            'trending.index' => 'trending',
            'trending.selected' => 'trending',
            'profile.index' => 'profile',
            'profile.password' => 'profile',
            'documents.index' => 'documents',
            'preview.full' => 'preview',
            
            // Admin/Editor pages
            'dashboard' => 'dashboard_admin',
            'dashboard.admin' => 'dashboard_admin',
            'isu.index' => 'isu_management',
            'isu.create' => 'isu_create',
            'isu.edit' => 'isu_edit',
            'trending.create' => 'trending_management',
            'trending.manual.create' => 'trending_management',
            'documents.create' => 'document_management',
            'users.index' => 'user_management',
            'settings.index' => 'settings',
            'analytics.index' => 'analytics_dashboard',
        ];
        
        if ($routeName && isset($routeMapping[$routeName])) {
            return $routeMapping[$routeName];
        }
        
        // Path patterns untuk semua role
        if ($path === '' || $path === '/' || $path === 'home') {
            return 'dashboard_viewer';
        }
        
        if ($path === 'dashboard') {
            return 'dashboard_admin';
        }
        
        if (str_contains($path, 'isu/') && !str_contains($path, 'edit') && !str_contains($path, 'create')) {
            return 'isu_detail';
        }
        
        if (str_contains($path, 'isu') && (str_contains($path, 'edit') || str_contains($path, 'create'))) {
            return str_contains($path, 'create') ? 'isu_create' : 'isu_edit';
        }
        
        if (str_contains($path, 'isu') && !str_contains($path, '/')) {
            return 'isu_management';
        }
        
        if (str_contains($path, 'trending')) {
            return str_contains($path, 'create') ? 'trending_management' : 'trending';
        }
        
        if (str_contains($path, 'users')) {
            return 'user_management';
        }
        
        if (str_contains($path, 'documents')) {
            return str_contains($path, 'create') || str_contains($path, 'edit') ? 'document_management' : 'documents';
        }
        
        if (str_contains($path, 'profile')) {
            return 'profile';
        }
        
        if (str_contains($path, 'settings')) {
            return 'settings';
        }
        
        if (str_contains($path, 'analytics')) {
            return 'analytics_dashboard';
        }
        
        return 'other';
    }
    
    /**
     * Tentukan apakah halaman harus ditrack berdasarkan role
     * Updated untuk mendukung tracking berdasarkan role
     */
    private function shouldTrackPage($pageName, $userRole): bool
    {
        // Halaman yang bisa ditrack untuk viewer
        $viewerPages = [
            'dashboard_viewer',
            'isu_detail',
            'trending',
            'profile',
            'documents',
            'preview'
        ];
        
        // Halaman yang bisa ditrack untuk admin/editor/verifikator
        $adminPages = [
            'dashboard_admin',
            'isu_management',
            'isu_create',
            'isu_edit',
            'trending_management',
            'document_management',
            'user_management',
            'settings',
            'analytics_dashboard'
        ];
        
        // Halaman yang bisa ditrack untuk semua role
        $commonPages = [
            'profile',
            'isu_detail', // Semua role bisa lihat detail isu
        ];
        
        // Cek berdasarkan role
        switch ($userRole) {
            case 'viewer':
                return in_array($pageName, array_merge($viewerPages, $commonPages));
                
            case 'admin':
            case 'editor':
            case 'verifikator1':
            case 'verifikator2':
                return in_array($pageName, array_merge($adminPages, $commonPages, $viewerPages));
                
            default:
                return in_array($pageName, $commonPages);
        }
    }
    
    /**
     * Track page visit dengan role information dan duration tracking
     */
    private function trackPageVisit(Request $request, $user)
    {
        // Update previous page duration if exists
        $previousAnalyticsId = session('current_analytics_id');
        if ($previousAnalyticsId) {
            $this->updatePreviousPageDuration($previousAnalyticsId);
        }
        
        $pageName = $this->determinePageName($request);
        $roleName = $user->getHighestRoleName();
        
        Log::info('CREATING ANALYTICS RECORD', [
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'page_name' => $pageName,
            'role_name' => $roleName,
            'url' => $request->fullUrl()
        ]);
        
        $analytics = UserAnalytics::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'page_url' => $request->fullUrl(),
            'page_name' => $pageName,
            'page_title' => $this->getPageTitle($pageName),
            'page_params' => [],
            'visited_at' => now(),
            'referrer' => $request->header('referer'),
            'is_bounce' => false,
            'role_name' => $roleName,
            'duration_seconds' => 0 // Set default 0 instead of null
        ]);
        
        // PENTING: Store analytics ID in session untuk JavaScript tracking
        session(['current_analytics_id' => $analytics->id]);
        
        Log::info('ANALYTICS RECORD CREATED', [
            'id' => $analytics->id,
            'user_id' => $analytics->user_id,
            'page_name' => $analytics->page_name,
            'role_name' => $analytics->role_name,
            'created_at' => $analytics->created_at,
            'stored_in_session' => true
        ]);
    }
    
    /**
     * Update duration untuk halaman sebelumnya
     */
    private function updatePreviousPageDuration($analyticsId)
    {
        try {
            $previous = UserAnalytics::find($analyticsId);
            if ($previous && !$previous->left_at && $previous->user_id == Auth::id()) {
                $duration = now()->diffInSeconds($previous->visited_at);
                
                // Validasi duration yang masuk akal
                if ($duration >= 0 && $duration <= 3600) { // 0 detik sampai 1 jam
                    $previous->update([
                        'left_at' => now(),
                        'duration_seconds' => $duration,
                        'is_bounce' => $duration < 10
                    ]);
                    
                    Log::info('Previous page duration updated', [
                        'analytics_id' => $analyticsId,
                        'duration' => $duration,
                        'is_bounce' => $duration < 10
                    ]);
                } else {
                    Log::warning('Invalid duration calculated, skipping update', [
                        'analytics_id' => $analyticsId,
                        'calculated_duration' => $duration,
                        'visited_at' => $previous->visited_at,
                        'now' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update previous page duration', [
                'analytics_id' => $analyticsId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get page title dengan support untuk halaman admin
     */
    private function getPageTitle($pageName): string
    {
        $titles = [
            // Viewer pages
            'dashboard_viewer' => 'Dashboard Viewer',
            'isu_detail' => 'Detail Isu',
            'trending' => 'Trending Topics',
            'profile' => 'Profil Pengguna',
            'documents' => 'Dokumen',
            'preview' => 'Preview',
            
            // Admin/Editor pages
            'dashboard_admin' => 'Dashboard Admin',
            'isu_management' => 'Manajemen Isu',
            'isu_create' => 'Buat Isu Baru',
            'isu_edit' => 'Edit Isu',
            'trending_management' => 'Manajemen Trending',
            'document_management' => 'Manajemen Dokumen',
            'user_management' => 'Manajemen User',
            'settings' => 'Pengaturan',
            'analytics_dashboard' => 'Analytics Dashboard'
        ];
        
        return $titles[$pageName] ?? 'Halaman Lainnya';
    }
}