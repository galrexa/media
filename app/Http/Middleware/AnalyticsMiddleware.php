<?php
// app/Http/Middleware/AnalyticsMiddleware.php - FIXED VERSION

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
     * FIXED: Proper error handling untuk mencegah blocking request
     */
    public function handle(Request $request, Closure $next)
    {
        \Log::info('=== ANALYTICS MIDDLEWARE START ===', [
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
        ]);
        
        try {
            // Skip debug dan development routes
            if ($this->shouldSkipRoute($request->path())) {
                \Log::info('SKIPPING ROUTE - in shouldSkipRoute list');
                return $next($request);
            }
            
            \Log::info('PROCESSING ANALYTICS...');
            $response = $next($request);
            \Log::info('ANALYTICS MIDDLEWARE - Got response from next middleware/controller');
            
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
                // PENTING: Jangan return error, lanjutkan request
            }
            
            \Log::info('=== ANALYTICS MIDDLEWARE END - SUCCESS ===');
            return $response;
            
        } catch (\Exception $e) {
            \Log::error('=== ANALYTICS MIDDLEWARE ERROR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl()
            ]);
            
            // PENTING: Jangan block request karena error analytics
            // Return next response anyway
            try {
                return $next($request);
            } catch (\Exception $nextError) {
                \Log::error('NEXT MIDDLEWARE ALSO FAILED', [
                    'error' => $nextError->getMessage()
                ]);
                throw $nextError;
            }
        }
    }
    
    /**
     * Process analytics tracking dengan isolated error handling
     */
    private function processAnalyticsTracking(Request $request)
    {
        try {
            // Cek authentication
            if (!Auth::check()) {
                Log::info('SKIP ANALYTICS: Not authenticated');
                return;
            }
            
            $user = Auth::user();
            
            // Cek user aktif
            if (isset($user->is_active) && !$user->is_active) {
                Log::info('SKIP ANALYTICS: User not active', ['user_id' => $user->id]);
                return;
            }
            
            // Check request conditions
            if (!$request->isMethod('GET')) {
                Log::info('SKIP ANALYTICS: Not GET request', ['method' => $request->method()]);
                return;
            }
            
            if ($request->ajax() || $request->expectsJson()) {
                Log::info('SKIP ANALYTICS: AJAX or JSON request');
                return;
            }
            
            // Get user role
            $roleName = $user->getHighestRoleName();
            
            // Determine page name
            $pageName = $this->determinePageName($request);
            
            // Check if should track page
            $shouldTrackPage = $this->shouldTrackPage($pageName, $roleName);
            
            if (!$shouldTrackPage) {
                Log::info('SKIP ANALYTICS: Page not trackable for this role', [
                    'page_name' => $pageName,
                    'user_role' => $roleName
                ]);
                return;
            }
            
            // Track the page visit
            $this->trackPageVisit($request, $user);
            
            Log::info('ANALYTICS TRACKING COMPLETED SUCCESSFULLY');
            
        } catch (\Exception $e) {
            Log::error('ANALYTICS TRACKING FAILED (request akan tetap dilanjutkan)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Jangan throw error - biarkan request lanjut
        }
    }
    
    /**
     * Tentukan apakah route harus di-skip
     */
    private function shouldSkipRoute($path): bool
    {
        $excludePaths = [
            'admin/analytics/chart-data',
            'admin/analytics/real-time',  
            'admin/analytics/export',
            'api/', 'dev/', 'sanctum/', 'storage/', 'up',
            'assets/', 'css/', 'js/', 'images/', 'favicon.ico',
            'notifications/', 'notifikasi/', 'reset-', 'show-', 'debug/',
            'test-', // Skip test routes
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
     */
    private function determinePageName(Request $request): string
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();
        
        // Route mapping untuk semua role
        $routeMapping = [
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
        
        // Path patterns fallback
        if (str_contains($path, 'isu') && str_contains($path, 'create')) {
            return 'isu_create';
        }
        
        return 'other';
    }
    
    /**
     * Tentukan apakah halaman harus ditrack berdasarkan role
     */
    private function shouldTrackPage($pageName, $userRole): bool
    {
        $adminPages = [
            'dashboard_admin', 'isu_management', 'isu_create', 'isu_edit',
            'trending_management', 'document_management', 'user_management',
            'settings', 'analytics_dashboard'
        ];
        
        $viewerPages = [
            'dashboard_viewer', 'isu_detail', 'trending', 'profile', 'documents', 'preview'
        ];
        
        $commonPages = ['profile', 'isu_detail'];
        
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
     * Track page visit dengan isolated error handling
     */
    private function trackPageVisit(Request $request, $user)
    {
        try {
            // Update previous page duration if exists
            $previousAnalyticsId = session('current_analytics_id');
            if ($previousAnalyticsId) {
                $this->updatePreviousPageDuration($previousAnalyticsId);
            }
            
            $pageName = $this->determinePageName($request);
            $roleName = $user->getHighestRoleName();
            
            // Check if UserAnalytics model exists
            if (!class_exists('App\\Models\\UserAnalytics')) {
                Log::warning('UserAnalytics model not found - skipping tracking');
                return;
            }
            
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
                'duration_seconds' => 0
            ]);
            
            session(['current_analytics_id' => $analytics->id]);
            
            Log::info('ANALYTICS RECORD CREATED', [
                'id' => $analytics->id,
                'page_name' => $analytics->page_name,
                'role_name' => $analytics->role_name
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to track page visit', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown'
            ]);
            // Jangan throw - biarkan request lanjut
        }
    }
    
    /**
     * Update duration untuk halaman sebelumnya
     */
    private function updatePreviousPageDuration($analyticsId)
    {
        try {
            if (!class_exists('App\\Models\\UserAnalytics')) {
                return;
            }
            
            $previous = UserAnalytics::find($analyticsId);
            if ($previous && !$previous->left_at && $previous->user_id == Auth::id()) {
                $duration = now()->diffInSeconds($previous->visited_at);
                
                if ($duration >= 0 && $duration <= 3600) {
                    $previous->update([
                        'left_at' => now(),
                        'duration_seconds' => $duration,
                        'is_bounce' => $duration < 10
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
     * Get page title
     */
    private function getPageTitle($pageName): string
    {
        $titles = [
            'dashboard_viewer' => 'Dashboard Viewer',
            'isu_detail' => 'Detail Isu',
            'trending' => 'Trending Topics',
            'profile' => 'Profil Pengguna',
            'documents' => 'Dokumen',
            'preview' => 'Preview',
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