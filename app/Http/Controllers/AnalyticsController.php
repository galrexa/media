<?php
// app/Http/Controllers/AnalyticsController.php - Phase 3: Multi-Role Analytics Support

namespace App\Http\Controllers;

use App\Models\UserAnalytics;
use App\Models\LoginHistory;
use App\Models\DailyAnalyticsSummary; 
use App\Models\HourlyAnalyticsSummary;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        // Role-based access middleware sudah dihandle di routes
        // Admin: bisa lihat semua
        // Editor/Verifikator: hanya viewer analytics
        // Viewer: hanya analytics sendiri
    }

    /**
     * Halaman utama analytics dashboard dengan role-based access
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $userRole = $user->getHighestRoleName();

            // DEBUG: Cek session
            Log::info('Analytics Dashboard Access', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'current_analytics_id' => session('current_analytics_id'),
                'session_id' => session()->getId(),
                'path' => $request->path()
            ]);
            
            // FORCE: Pastikan ada analytics ID untuk dashboard ini
            if (!session('current_analytics_id')) {
                $analytics = UserAnalytics::create([
                    'user_id' => $user->id,
                    'session_id' => session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'page_url' => $request->fullUrl(),
                    'page_name' => 'analytics_dashboard',
                    'page_title' => 'Analytics Dashboard',
                    'visited_at' => now(),
                    'role_name' => $userRole,
                    'duration_seconds' => 0
                ]);
                
                session(['current_analytics_id' => $analytics->id]);
                
                Log::info('Created analytics record for dashboard', [
                    'analytics_id' => $analytics->id,
                    'user_id' => $user->id,
                    'user_role' => $userRole
                ]);
            }
            
            // Default periode: 7 hari terakhir
            $period = $request->get('period', 'week');
            $customStart = $request->get('start_date');
            $customEnd = $request->get('end_date');
            
            // Tentukan range tanggal
            [$startDate, $endDate] = $this->getDateRange($period, $customStart, $customEnd);
            
            // Data berdasarkan role permission
            $overview = $this->getOverviewStats($startDate, $endDate, $userRole);
            $peakHours = $this->getPeakHoursData($startDate, $endDate, $userRole);
            $dailyTrends = $this->getDailyTrendsData($startDate, $endDate, $userRole);
            $popularPages = $this->getPopularPagesData($startDate, $endDate, $userRole);
            $activeUsers = $this->getActiveUsersData($startDate, $endDate, $userRole);
            
            // Role-specific statistics
            $roleStats = $this->getRoleStatistics($startDate, $endDate, $userRole);
            $loginStats = $this->getLoginStatistics($startDate, $endDate, $userRole);
            
            // User access info
            $accessInfo = $this->getUserAccessInfo($userRole);
            
            return view('analytics.index', compact(
                'overview',
                'peakHours', 
                'dailyTrends',
                'popularPages',
                'activeUsers',
                'roleStats',
                'loginStats',
                'accessInfo',
                'userRole',
                'period',
                'startDate',
                'endDate'
            ));
            
        } catch (\Exception $e) {
            Log::error('Analytics dashboard error', [
                'user_id' => Auth::id(),
                'user_role' => $userRole ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('analytics.index', $this->getDefaultData($userRole ?? 'viewer'));
        }
    }

    /**
     * API endpoint untuk data chart dengan role-based filtering
     */
    public function getChartData(Request $request)
    {
        try {
            $user = Auth::user();
            $userRole = $user->getHighestRoleName();
            
            $type = $request->get('type', 'daily');
            $period = $request->get('period', 'week');
            $customStart = $request->get('start_date');
            $customEnd = $request->get('end_date');
            
            [$startDate, $endDate] = $this->getDateRange($period, $customStart, $customEnd);
            
            $data = match ($type) {
                'overview' => $this->getOverviewStats($startDate, $endDate, $userRole),
                'daily' => $this->getDailyTrendsData($startDate, $endDate, $userRole),
                'hourly' => $this->getPeakHoursData($startDate, $endDate, $userRole),
                'pages' => $this->getPopularPagesData($startDate, $endDate, $userRole),
                'users' => $this->getActiveUsersData($startDate, $endDate, $userRole),
                'roles' => $this->getRoleStatistics($startDate, $endDate, $userRole),
                'logins' => $this->getLoginStatistics($startDate, $endDate, $userRole),
                default => []
            };
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Analytics chart data error', [
                'user_id' => Auth::id(),
                'type' => $request->get('type'),
                'period' => $request->get('period'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json($this->getEmptyDataForType($request->get('type', 'daily')));
        }
    }

    /**
     * Real-time analytics data dengan role filtering
     */
    public function getRealTimeData()
    {
        try {
            $user = Auth::user();
            $userRole = $user->getHighestRoleName();
            $today = Carbon::today();
            
            // Base query berdasarkan role
            $baseQuery = $this->getBaseAnalyticsQuery($userRole);
            
            // Statistik hari ini dari UserAnalytics
            $todayAnalytics = (clone $baseQuery)
                ->whereDate('visited_at', $today)
                ->selectRaw('
                    COUNT(*) as today_visits,
                    COUNT(DISTINCT user_id) as today_unique_visitors,
                    COUNT(DISTINCT session_id) as today_sessions
                ')
                ->first();
            
            // Login statistics hari ini
            $todayLogins = $this->getBaseLoginQuery($userRole)
                ->whereDate('login_at', $today)
                ->selectRaw('
                    COUNT(*) as today_logins,
                    COUNT(DISTINCT user_id) as today_unique_logins
                ')
                ->first();
            
            // Users yang aktif sekarang (berdasarkan last_api_login dalam 30 menit terakhir)
            $activeNow = $this->getBaseUserQuery($userRole)
                ->where('last_api_login', '>=', Carbon::now()->subMinutes(30))
                ->where('is_active', 1)
                ->count();
            
            return response()->json([
                'today_visits' => $todayAnalytics->today_visits ?? 0,
                'today_unique_visitors' => $todayAnalytics->today_unique_visitors ?? 0,
                'today_sessions' => $todayAnalytics->today_sessions ?? 0,
                'today_logins' => $todayLogins->today_logins ?? 0,
                'today_unique_logins' => $todayLogins->today_unique_logins ?? 0,
                'active_now' => $activeNow,
                'user_role' => $userRole,
                'last_updated' => now()->format('H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Real-time data error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'active_now' => 0,
                'user_role' => Auth::user()->getHighestRoleName(),
                'last_updated' => now()->format('H:i:s'),
                'error' => 'Failed to fetch real-time data'
            ]);
        }
    }

    /**
     * Get overview statistics berdasarkan role access
     */
    private function getOverviewStats($startDate, $endDate, $userRole)
    {
        try {
            // Cek apakah ada data UserAnalytics yang accessible
            $hasAnalyticsData = $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->exists();
            
            if ($hasAnalyticsData) {
                // Gunakan data dari UserAnalytics
                $stats = $this->getBaseAnalyticsQuery($userRole)
                    ->whereBetween('visited_at', [$startDate, $endDate])
                    ->selectRaw('
                        COUNT(*) as total_page_views,
                        COUNT(DISTINCT user_id) as unique_visitors,
                        COUNT(DISTINCT session_id) as total_sessions,
                        AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                        SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as total_bounces
                    ')
                    ->first();
                
                $bounceRate = $stats->total_page_views > 0 
                    ? ($stats->total_bounces / $stats->total_page_views) * 100 
                    : 0;
                
                // Login statistics
                $loginStats = $this->getBaseLoginQuery($userRole)
                    ->whereBetween('login_at', [$startDate, $endDate])
                    ->selectRaw('
                        COUNT(*) as total_logins,
                        COUNT(DISTINCT user_id) as unique_login_users,
                        AVG(COALESCE(session_duration, 0)) as avg_session_duration
                    ')
                    ->first();
                
                return [
                    'total_page_views' => $stats->total_page_views ?? 0,
                    'unique_visitors' => $stats->unique_visitors ?? 0,
                    'total_sessions' => $stats->total_sessions ?? 0,
                    'avg_duration' => round($stats->avg_duration ?? 0, 2),
                    'bounce_rate' => round($bounceRate, 2),
                    'total_logins' => $loginStats->total_logins ?? 0,
                    'unique_login_users' => $loginStats->unique_login_users ?? 0,
                    'avg_session_duration' => round($loginStats->avg_session_duration ?? 0, 2),
                    'data_source' => 'analytics'
                ];
            } else {
                // Fallback ke data login saja
                $loginStats = $this->getBaseLoginQuery($userRole)
                    ->whereBetween('login_at', [$startDate, $endDate])
                    ->selectRaw('
                        COUNT(*) as total_logins,
                        COUNT(DISTINCT user_id) as unique_users,
                        AVG(COALESCE(session_duration, 0)) as avg_session_duration
                    ')
                    ->first();
                
                return [
                    'total_page_views' => $loginStats->total_logins ?? 0,
                    'unique_visitors' => $loginStats->unique_users ?? 0,
                    'total_sessions' => $loginStats->total_logins ?? 0,
                    'avg_duration' => round($loginStats->avg_session_duration ?? 0, 2),
                    'bounce_rate' => 0,
                    'total_logins' => $loginStats->total_logins ?? 0,
                    'unique_login_users' => $loginStats->unique_users ?? 0,
                    'avg_session_duration' => round($loginStats->avg_session_duration ?? 0, 2),
                    'data_source' => 'login_only'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Overview stats error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultOverview();
        }
    }

    /**
     * Get daily trends data dengan role filtering
     */
    private function getDailyTrendsData($startDate, $endDate, $userRole)
    {
        try {
            // Cek apakah ada data UserAnalytics
            $hasAnalyticsData = $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->exists();
            
            if ($hasAnalyticsData) {
                // Data dari UserAnalytics dengan role breakdown
                $analyticsData = $this->getBaseAnalyticsQuery($userRole)
                    ->whereBetween('visited_at', [$startDate, $endDate])
                    ->selectRaw('
                        DATE(visited_at) as date,
                        page_name,
                        role_name,
                        COUNT(*) as visits,
                        COUNT(DISTINCT user_id) as unique_visitors
                    ')
                    ->groupBy('date', 'page_name', 'role_name')
                    ->orderBy('date')
                    ->get();
                
                // Group by date untuk chart
                $groupedData = $analyticsData->groupBy('date');
                
                $chartData = [];
                $period = Carbon::parse($startDate);
                
                while ($period->lte($endDate)) {
                    $dateStr = $period->format('Y-m-d');
                    $dayData = $groupedData->get($dateStr, collect());
                    
                    $chartData[] = [
                        'date' => $dateStr,
                        'date_label' => $period->format('d M'),
                        'dashboard_viewer' => $dayData->where('page_name', 'dashboard_viewer')->sum('visits'),
                        'dashboard_admin' => $dayData->where('page_name', 'dashboard_admin')->sum('visits'),
                        'isu_detail' => $dayData->where('page_name', 'isu_detail')->sum('visits'),
                        'isu_management' => $dayData->where('page_name', 'isu_management')->sum('visits'),
                        'trending' => $dayData->where('page_name', 'trending')->sum('visits'),
                        'total_visits' => $dayData->sum('visits'),
                        'unique_visitors' => $dayData->sum('unique_visitors'),
                        'roles' => $dayData->groupBy('role_name')->map(function($roleData) {
                            return [
                                'visits' => $roleData->sum('visits'),
                                'unique_visitors' => $roleData->sum('unique_visitors')
                            ];
                        })
                    ];
                    
                    $period->addDay();
                }
            } else {
                // Fallback ke data login
                $loginData = $this->getBaseLoginQuery($userRole)
                    ->whereBetween('login_at', [$startDate, $endDate])
                    ->selectRaw('
                        DATE(login_at) as date,
                        login_type,
                        role_name,
                        COUNT(*) as total_logins,
                        COUNT(DISTINCT user_id) as unique_users
                    ')
                    ->groupBy('date', 'login_type', 'role_name')
                    ->orderBy('date')
                    ->get();
                
                $chartData = [];
                $period = Carbon::parse($startDate);
                
                while ($period->lte($endDate)) {
                    $dateStr = $period->format('Y-m-d');
                    $dayData = $loginData->where('date', $dateStr);
                    
                    $visits = $dayData->sum('total_logins');
                    $unique = $dayData->sum('unique_users');
                    
                    $chartData[] = [
                        'date' => $dateStr,
                        'date_label' => $period->format('d M'),
                        'dashboard_viewer' => $dayData->where('role_name', 'viewer')->sum('total_logins'),
                        'dashboard_admin' => $dayData->whereIn('role_name', ['admin', 'editor'])->sum('total_logins'),
                        'isu_detail' => 0,
                        'isu_management' => 0,
                        'trending' => 0,
                        'total_visits' => $visits,
                        'unique_visitors' => $unique,
                        'roles' => $dayData->groupBy('role_name')->map(function($roleData) {
                            return [
                                'visits' => $roleData->sum('total_logins'),
                                'unique_visitors' => $roleData->sum('unique_users')
                            ];
                        })
                    ];
                    
                    $period->addDay();
                }
            }
            
            return collect($chartData);
            
        } catch (\Exception $e) {
            Log::error('Daily trends data error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get peak hours data dengan role filtering
     */
    private function getPeakHoursData($startDate, $endDate, $userRole)
    {
        try {
            $hasAnalyticsData = $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->exists();
            
            if ($hasAnalyticsData) {
                $hourlyData = $this->getBaseAnalyticsQuery($userRole)
                    ->whereBetween('visited_at', [$startDate, $endDate])
                    ->selectRaw('
                        HOUR(visited_at) as hour,
                        role_name,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT user_id) as unique_visitors
                    ')
                    ->groupBy('hour', 'role_name')
                    ->orderBy('hour')
                    ->get();
            } else {
                $hourlyData = $this->getBaseLoginQuery($userRole)
                    ->whereBetween('login_at', [$startDate, $endDate])
                    ->selectRaw('
                        HOUR(login_at) as hour,
                        role_name,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT user_id) as unique_visitors
                    ')
                    ->groupBy('hour', 'role_name')
                    ->orderBy('hour')
                    ->get();
            }
            
            // Format untuk chart (0-23 jam)
            $chartData = [];
            for ($i = 0; $i < 24; $i++) {
                $hourData = $hourlyData->where('hour', $i);
                $chartData[] = [
                    'hour' => $i,
                    'hour_label' => str_pad($i, 2, '0', STR_PAD_LEFT) . ':00',
                    'visits' => $hourData->sum('total_visits'),
                    'unique_visitors' => $hourData->sum('unique_visitors'),
                    'by_role' => $hourData->groupBy('role_name')->map(function($roleData) {
                        return [
                            'visits' => $roleData->sum('total_visits'),
                            'unique_visitors' => $roleData->sum('unique_visitors')
                        ];
                    })
                ];
            }
            
            return $chartData;
            
        } catch (\Exception $e) {
            Log::error('Peak hours data error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return $this->getEmptyHourlyData();
        }
    }

    /**
     * Get popular pages data dengan role access filtering
     */
    private function getPopularPagesData($startDate, $endDate, $userRole)
    {
        try {
            $hasAnalyticsData = $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->exists();
            
            if (!$hasAnalyticsData) {
                return collect([]);
            }
            
            return $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->selectRaw('
                    page_name,
                    page_title,
                    role_name,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT user_id) as unique_visitors,
                    AVG(COALESCE(NULLIF(duration_seconds, -1), 0)) as avg_duration,
                    SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounce_count,
                    COUNT(CASE WHEN duration_seconds > 0 THEN 1 END) as records_with_duration,
                    MAX(duration_seconds) as max_duration,
                    MIN(duration_seconds) as min_duration
                ')
                ->groupBy('page_name', 'page_title', 'role_name')
                ->orderBy('total_visits', 'desc')
                ->limit(15)
                ->get()
                ->map(function ($item) {
                    $bounceRate = $item->total_visits > 0 
                        ? ($item->bounce_count / $item->total_visits) * 100 
                        : 0;
                    
                    // Debug log untuk setiap item
                    Log::info('Page data debug', [
                        'page' => $item->page_name,
                        'role' => $item->role_name,
                        'visits' => $item->total_visits,
                        'avg_duration_raw' => $item->avg_duration,
                        'records_with_duration' => $item->records_with_duration,
                        'max_duration' => $item->max_duration,
                        'min_duration' => $item->min_duration
                    ]);
                    
                    return [
                        'page_name' => $item->page_name,
                        'page_title' => $item->page_title,
                        'role_name' => $item->role_name,
                        'total_visits' => $item->total_visits,
                        'unique_visitors' => $item->unique_visitors,
                        'avg_duration' => round($item->avg_duration ?? 0, 2),
                        'bounce_rate' => round($bounceRate, 2),
                        'debug_info' => [
                            'records_with_duration' => $item->records_with_duration,
                            'max_duration' => $item->max_duration,
                            'min_duration' => $item->min_duration
                        ]
                    ];
                });
                
        } catch (\Exception $e) {
            Log::error('Popular pages data error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get active users data dengan comprehensive tracking
     */
    private function getActiveUsersData($startDate, $endDate, $userRole)
    {
        try {
            // Kombinasi data dari analytics dan login history
            $users = $this->getBaseUserQuery($userRole)
                ->whereBetween('last_api_login', [$startDate, $endDate])
                ->select([
                    'id',
                    'name',
                    'username',
                    'position',
                    'department',
                    'last_api_login'
                ])
                ->orderBy('last_api_login', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($user) use ($startDate, $endDate, $userRole) {
                    // Analytics data untuk user ini
                    $analyticsData = $this->getBaseAnalyticsQuery($userRole)
                        ->where('user_id', $user->id)
                        ->whereBetween('visited_at', [$startDate, $endDate])
                        ->selectRaw('
                            COUNT(*) as total_visits,
                            COUNT(DISTINCT page_name) as pages_visited,
                            SUM(COALESCE(duration_seconds, 0)) as total_duration
                        ')
                        ->first();
                    
                    // Login history untuk user ini
                    $loginData = $this->getBaseLoginQuery($userRole)
                        ->where('user_id', $user->id)
                        ->whereBetween('login_at', [$startDate, $endDate])
                        ->selectRaw('
                            COUNT(*) as total_logins,
                            COUNT(DISTINCT login_type) as login_methods,
                            AVG(COALESCE(session_duration, 0)) as avg_session_duration
                        ')
                        ->first();
                    
                    $totalVisits = $analyticsData && $analyticsData->total_visits > 0 
                        ? $analyticsData->total_visits 
                        : ($loginData ? $loginData->total_logins : 0);
                    
                    $pagesVisited = $analyticsData && $analyticsData->pages_visited > 0 
                        ? $analyticsData->pages_visited 
                        : 1;
                    
                    return [
                        'user' => (object)[
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'position' => $user->position,
                            'department' => $user->department,
                        ],
                        'total_visits' => $totalVisits,
                        'pages_visited' => $pagesVisited,
                        'total_logins' => $loginData ? $loginData->total_logins : 0,
                        'login_methods' => $loginData ? $loginData->login_methods : 0,
                        'last_visit' => $user->last_api_login ? Carbon::parse($user->last_api_login) : null,
                        'total_duration' => $analyticsData ? $analyticsData->total_duration : 0,
                        'avg_session_duration' => $loginData ? round($loginData->avg_session_duration, 2) : 0
                    ];
                });
            
            return $users;
                
        } catch (\Exception $e) {
            Log::error('Active users data error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    public function updateDuration(Request $request)
    {
        try {
            $analyticsId = $request->input('analytics_id');
            $duration = $request->input('duration');
            
            // Enhanced validation
            if (!$analyticsId || !is_numeric($duration) || $duration < 0 || $duration > 86400) {
                Log::warning('Invalid duration data', [
                    'analytics_id' => $analyticsId,
                    'duration' => $duration,
                    'user_id' => Auth::id()
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid duration']);
            }
            
            $analytics = UserAnalytics::find($analyticsId);
            if ($analytics && $analytics->user_id == Auth::id()) {
                // Pastikan duration valid dan masuk akal
                $safeDuration = max(0, min((int)$duration, 86400)); // Max 24 jam
                
                $analytics->update([
                    'duration_seconds' => $safeDuration,
                    'left_at' => now(),
                    'is_bounce' => $safeDuration < 10
                ]);
                
                Log::info('Duration updated safely', [
                    'analytics_id' => $analyticsId,
                    'original_duration' => $duration,
                    'safe_duration' => $safeDuration,
                    'user_id' => Auth::id()
                ]);
                
                return response()->json(['success' => true]);
            }
            
            return response()->json(['success' => false, 'message' => 'Record not found']);
            
        } catch (\Exception $e) {
            Log::error('Update duration error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * BARU: Get role statistics
     */
    private function getRoleStatistics($startDate, $endDate, $userRole)
    {
        try {
            if ($userRole !== 'admin') {
                return collect([]);
            }
            
            // Get role statistics from users table with joins
            $roleStats = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.is_active', 1)
                ->select('roles.name as role_name', 'roles.name as role_label')
                ->selectRaw('COUNT(users.id) as total_users')
                ->groupBy('roles.id', 'roles.name')
                ->get();
            
            // Get analytics stats if available
            $analyticsStats = collect([]);
            if (class_exists(UserAnalytics::class)) {
                $analyticsStats = DB::table('user_analytics')
                    ->join('users', 'user_analytics.user_id', '=', 'users.id')
                    ->join('roles', 'users.role_id', '=', 'roles.id')
                    ->whereBetween('user_analytics.visited_at', [$startDate, $endDate])
                    ->where('users.is_active', 1)
                    ->select('roles.name as role_name')
                    ->selectRaw('
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT user_analytics.user_id) as unique_visitors,
                        COUNT(DISTINCT user_analytics.page_name) as pages_accessed,
                        AVG(COALESCE(user_analytics.duration_seconds, 0)) as avg_duration
                    ')
                    ->groupBy('roles.name')
                    ->get();
            }
            
            // Get login stats from users table (fallback)
            $loginStats = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->whereBetween('users.last_api_login', [$startDate, $endDate])
                ->where('users.is_active', 1)
                ->select('roles.name as role_name')
                ->selectRaw('
                    COUNT(*) as total_logins,
                    COUNT(DISTINCT users.id) as unique_users
                ')
                ->groupBy('roles.name')
                ->get();
            
            // Combine data
            $allRoles = ['admin', 'editor', 'verifikator1', 'verifikator2', 'viewer'];
            $result = [];
            
            foreach ($allRoles as $role) {
                $analytics = $analyticsStats->where('role_name', $role)->first();
                $logins = $loginStats->where('role_name', $role)->first();
                $roleInfo = $roleStats->where('role_name', $role)->first();
                
                $result[] = [
                    'role_name' => $role,
                    'role_label' => ucfirst($role),
                    'total_visits' => $analytics ? $analytics->total_visits : 0,
                    'unique_visitors' => $analytics ? $analytics->unique_visitors : 0,
                    'pages_accessed' => $analytics ? $analytics->pages_accessed : 0,
                    'avg_duration' => $analytics ? round($analytics->avg_duration, 2) : 0,
                    'total_logins' => $logins ? $logins->total_logins : 0,
                    'unique_users' => $logins ? $logins->unique_users : 0,
                    'total_users' => $roleInfo ? $roleInfo->total_users : 0
                ];
            }
            
            return collect($result);
            
        } catch (\Exception $e) {
            Log::error('Role statistics error', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * BARU: Get login statistics
     */
    private function getLoginStatistics($startDate, $endDate, $userRole)
    {
        try {
            $stats = $this->getBaseLoginQuery($userRole)
                ->whereBetween('login_at', [$startDate, $endDate])
                ->selectRaw('
                    login_type,
                    COUNT(*) as total_logins,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(COALESCE(session_duration, 0)) as avg_duration,
                    MAX(login_at) as latest_login
                ')
                ->groupBy('login_type')
                ->get();
            
            return $stats->map(function($item) {
                return [
                    'login_type' => $item->login_type,
                    'login_type_label' => match($item->login_type) {
                        'web' => 'Web Login',
                        'api' => 'API Login',
                        'api_activity' => 'API Activity',
                        default => 'Unknown'
                    },
                    'total_logins' => $item->total_logins,
                    'unique_users' => $item->unique_users,
                    'avg_duration' => round($item->avg_duration, 2),
                    'latest_login' => $item->latest_login
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Login statistics error', [
                'user_role' => $userRole,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Helper: Get base analytics query berdasarkan role
     */
    private function getBaseAnalyticsQuery($userRole)
    {
        $query = UserAnalytics::activeUsersOnly();
        
        return match($userRole) {
            'admin' => $query, // Admin bisa lihat semua
            'editor', 'verifikator1', 'verifikator2' => $query->where('role_name', 'viewer'), // Hanya viewer
            default => $query->where('user_id', Auth::id()) // Hanya diri sendiri
        };
    }

    /**
     * Helper: Get base login query berdasarkan role
     */
    private function getBaseLoginQuery($userRole)
    {
        $query = LoginHistory::activeUsersOnly();
        
        return match($userRole) {
            'admin' => $query, // Admin bisa lihat semua
            'editor', 'verifikator1', 'verifikator2' => $query->where('role_name', 'viewer'), // Hanya viewer
            default => $query->where('user_id', Auth::id()) // Hanya diri sendiri
        };
    }

    /**
     * Helper: Get base user query berdasarkan role
     */
    private function getBaseUserQuery($userRole)
    {
        $query = User::where('is_active', 1);
        
        return match($userRole) {
            'admin' => $query, // Admin bisa lihat semua user
            'editor', 'verifikator1', 'verifikator2' => $query->whereHas('role', function($q) {
                $q->where('name', 'viewer');
            }), // Hanya viewer users
            default => $query->where('id', Auth::id()) // Hanya diri sendiri
        };
    }

    /**
     * Get user access information
     */
    private function getUserAccessInfo($userRole)
    {
        return [
            'role' => $userRole,
            'can_see_all_roles' => $userRole === 'admin',
            'can_see_viewer_only' => in_array($userRole, ['editor', 'verifikator1', 'verifikator2']),
            'can_see_own_only' => !in_array($userRole, ['admin', 'editor', 'verifikator1', 'verifikator2']),
            'description' => match($userRole) {
                'admin' => 'Anda dapat melihat analytics semua role pengguna',
                'editor', 'verifikator1', 'verifikator2' => 'Anda dapat melihat analytics pengguna viewer saja',
                default => 'Anda hanya dapat melihat analytics aktivitas sendiri'
            }
        ];
    }

    /**
     * Helper: Tentukan range tanggal berdasarkan periode
     */
    private function getDateRange($period, $customStart = null, $customEnd = null)
    {
        if ($period === 'custom' && $customStart && $customEnd) {
            return [
                Carbon::parse($customStart)->startOfDay(),
                Carbon::parse($customEnd)->endOfDay()
            ];
        }
        
        switch ($period) {
            case 'today':
                return [Carbon::today(), Carbon::now()];
            case 'week':
                return [Carbon::now()->subWeek(), Carbon::now()];
            case 'month':
                return [Carbon::now()->subMonth(), Carbon::now()];
            case '3months':
                return [Carbon::now()->subMonths(3), Carbon::now()];
            default:
                return [Carbon::now()->subWeek(), Carbon::now()];
        }
    }

    /**
     * Default overview untuk error cases
     */
    private function getDefaultOverview()
    {
        return [
            'total_page_views' => 0,
            'unique_visitors' => 0,
            'total_sessions' => 0,
            'avg_duration' => 0,
            'bounce_rate' => 0,
            'total_logins' => 0,
            'unique_login_users' => 0,
            'avg_session_duration' => 0,
            'data_source' => 'default'
        ];
    }

    /**
     * Empty hourly data untuk chart
     */
    private function getEmptyHourlyData()
    {
        $chartData = [];
        for ($i = 0; $i < 24; $i++) {
            $chartData[] = [
                'hour' => $i,
                'hour_label' => str_pad($i, 2, '0', STR_PAD_LEFT) . ':00',
                'visits' => 0,
                'unique_visitors' => 0,
                'by_role' => []
            ];
        }
        return $chartData;
    }

    /**
     * Get empty data untuk different chart types
     */
    private function getEmptyDataForType($type)
    {
        return match($type) {
            'overview' => $this->getDefaultOverview(),
            'daily' => [],
            'hourly' => $this->getEmptyHourlyData(),
            'pages' => [],
            'users' => [],
            'roles' => [],
            'logins' => [],
            default => []
        };
    }

    /**
     * Get default data for error cases
     */
    private function getDefaultData($userRole)
    {
        return [
            'overview' => $this->getDefaultOverview(),
            'peakHours' => $this->getEmptyHourlyData(),
            'dailyTrends' => collect([]),
            'popularPages' => collect([]),
            'activeUsers' => collect([]),
            'roleStats' => collect([]),
            'loginStats' => collect([]),
            'accessInfo' => $this->getUserAccessInfo($userRole),
            'userRole' => $userRole,
            'period' => 'week',
            'startDate' => Carbon::now()->subWeek(),
            'endDate' => Carbon::now(),
            'error' => true
        ];
    }

    /**
     * Cleanup old analytics dan login history data
     */
    public function cleanup(Request $request)
    {
        // Hanya admin yang bisa cleanup
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can perform cleanup');
        }

        $request->validate([
            'before_date' => 'required|date|before:today'
        ]);
        
        $beforeDate = Carbon::parse($request->before_date);
        
        try {
            DB::beginTransaction();
            
            // Hapus data analytics
            $deletedAnalytics = UserAnalytics::where('visited_at', '<', $beforeDate)->delete();
            
            // Hapus data login history
            $deletedLoginHistory = LoginHistory::where('login_at', '<', $beforeDate)->delete();
            
            // Hapus summary jika ada
            $deletedDaily = 0;
            $deletedHourly = 0;
            
            if (class_exists(DailyAnalyticsSummary::class)) {
                $deletedDaily = DailyAnalyticsSummary::where('date', '<', $beforeDate)->delete();
            }
            
            if (class_exists(HourlyAnalyticsSummary::class)) {
                $deletedHourly = HourlyAnalyticsSummary::where('date', '<', $beforeDate)->delete();
            }
            
            DB::commit();
            
            $message = "Berhasil menghapus {$deletedAnalytics} record analytics, {$deletedLoginHistory} record login history";
            if ($deletedDaily > 0) {
                $message .= ", {$deletedDaily} summary harian";
            }
            if ($deletedHourly > 0) {
                $message .= ", {$deletedHourly} summary hourly";
            }
            $message .= " sebelum tanggal {$beforeDate->format('d M Y')}";
            
            Log::info('Analytics cleanup completed', [
                'user_id' => Auth::id(),
                'before_date' => $beforeDate,
                'deleted_analytics' => $deletedAnalytics,
                'deleted_login_history' => $deletedLoginHistory,
                'deleted_daily' => $deletedDaily,
                'deleted_hourly' => $deletedHourly
            ]);
            
            return redirect()->route('analytics.index')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cleanup error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Estimate cleanup data untuk konfirmasi
     */
    public function estimateCleanup(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'before_date' => 'required|date|before:today'
        ]);
        
        $beforeDate = Carbon::parse($request->before_date);
        
        try {
            $analyticsCount = UserAnalytics::where('visited_at', '<', $beforeDate)->count();
            $loginHistoryCount = LoginHistory::where('login_at', '<', $beforeDate)->count();
            
            $dailyCount = 0;
            $hourlyCount = 0;
            
            if (class_exists(DailyAnalyticsSummary::class)) {
                $dailyCount = DailyAnalyticsSummary::where('date', '<', $beforeDate)->count();
            }
            
            if (class_exists(HourlyAnalyticsSummary::class)) {
                $hourlyCount = HourlyAnalyticsSummary::where('date', '<', $beforeDate)->count();
            }
            
            return response()->json([
                'success' => true,
                'before_date' => $beforeDate->format('d M Y'),
                'analytics_records' => $analyticsCount,
                'login_history_records' => $loginHistoryCount,
                'daily_summary_records' => $dailyCount,
                'hourly_summary_records' => $hourlyCount,
                'total_records' => $analyticsCount + $loginHistoryCount + $dailyCount + $hourlyCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error estimating cleanup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export analytics data berdasarkan role access
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $userRole = $user->getHighestRoleName();
            
            $format = $request->get('format', 'csv');
            $period = $request->get('period', 'week');
            $customStart = $request->get('start_date');
            $customEnd = $request->get('end_date');
            
            [$startDate, $endDate] = $this->getDateRange($period, $customStart, $customEnd);
            
            // Check apakah ada data analytics yang accessible
            $hasAnalyticsData = $this->getBaseAnalyticsQuery($userRole)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->exists();
            
            $filename = "analytics_{$userRole}_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.{$format}";
            
            if ($hasAnalyticsData) {
                // Export data analytics
                $data = $this->getBaseAnalyticsQuery($userRole)
                    ->with('user:id,name,username')
                    ->whereBetween('visited_at', [$startDate, $endDate])
                    ->orderBy('visited_at', 'desc')
                    ->get();
                
                if ($format === 'csv') {
                    return $this->exportAnalyticsToCsv($data, $filename);
                }
            } else {
                // Export data login history
                $data = $this->getBaseLoginQuery($userRole)
                    ->with('user:id,name,username')
                    ->whereBetween('login_at', [$startDate, $endDate])
                    ->orderBy('login_at', 'desc')
                    ->get();
                
                $filename = "login_history_{$userRole}_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.{$format}";
                
                if ($format === 'csv') {
                    return $this->exportLoginHistoryToCsv($data, $filename);
                }
            }
            
            return redirect()->back()->with('error', 'Format export belum didukung');
            
        } catch (\Exception $e) {
            Log::error('Export error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Export analytics data ke CSV
     */
    private function exportAnalyticsToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, [
                'Tanggal Kunjungan',
                'User',
                'Role',
                'Halaman',
                'Judul Halaman',
                'Durasi (detik)',
                'IP Address',
                'Bounce'
            ]);
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->visited_at->format('Y-m-d H:i:s'),
                    $row->user ? $row->user->name : 'User #' . $row->user_id,
                    $row->role_name ?? 'Unknown',
                    $row->page_name,
                    $row->page_title,
                    $row->duration_seconds ?? 0,
                    $row->ip_address,
                    $row->is_bounce ? 'Ya' : 'Tidak'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export login history ke CSV
     */
    private function exportLoginHistoryToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, [
                'Tanggal Login',
                'User',
                'Role',
                'Tipe Login',
                'Durasi Session (detik)',
                'IP Address',
                'Status'
            ]);
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->login_at->format('Y-m-d H:i:s'),
                    $row->user ? $row->user->name : 'User #' . $row->user_id,
                    $row->role_name ?? 'Unknown',
                    match($row->login_type) {
                        'web' => 'Web Login',
                        'api' => 'API Login',
                        'api_activity' => 'API Activity',
                        default => 'Unknown'
                    },
                    $row->session_duration ?? 0,
                    $row->ip_address,
                    $row->logout_at ? 'Completed' : 'Active'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Optimize database untuk performance
     */
    public function optimize(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can optimize database');
        }

        try {
            DB::beginTransaction();
            
            // Optimize user_analytics table
            DB::statement('OPTIMIZE TABLE user_analytics');
            
            // Optimize login_history table
            DB::statement('OPTIMIZE TABLE login_history');
            
            // Update statistics
            DB::statement('ANALYZE TABLE user_analytics');
            DB::statement('ANALYZE TABLE login_history');
            
            DB::commit();
            
            Log::info('Database optimization completed', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);
            
            return redirect()->back()->with('success', 'Database berhasil dioptimasi untuk performa yang lebih baik.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database optimization error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengoptimasi database: ' . $e->getMessage());
        }
    }
}