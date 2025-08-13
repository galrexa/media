<?php
// app/Models/UserAnalytics.php - Updated dengan support untuk semua role

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserAnalytics extends Model
{
    use HasFactory;

    protected $table = 'user_analytics';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'page_url',
        'page_name',
        'page_title',
        'page_params',
        'visited_at',
        'left_at',
        'duration_seconds',
        'referrer',
        'is_bounce',
        'role_name' // TAMBAHAN: field untuk menyimpan role user
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'left_at' => 'datetime',
        'page_params' => 'array',
        'is_bounce' => 'boolean',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * UPDATED: Scope untuk filter berdasarkan role (menggantikan viewersOnly)
     */
    public function scopeByRole($query, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $query->whereIn('role_name', $roles);
    }

    /**
     * BACKWARD COMPATIBILITY: Scope untuk viewer saja
     */
    public function scopeViewersOnly($query)
    {
        return $query->where('role_name', 'viewer');
    }

    /**
     * BARU: Scope untuk filter berdasarkan akses user yang sedang login
     */
    public function scopeAccessibleBy($query, $user)
    {
        if ($user->isAdmin()) {
            // Admin bisa lihat analytics semua role
            return $query;
        } elseif ($user->isEditor() || $user->hasRole('verifikator1') || $user->hasRole('verifikator2')) {
            // Editor dan verifikator hanya bisa lihat analytics viewer
            return $query->where('role_name', 'viewer');
        } else {
            // Role lain hanya bisa lihat analytics sendiri
            return $query->where('user_id', $user->id);
        }
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopePeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('visited_at', [$startDate, $endDate]);
    }

    /**
     * Scope untuk halaman tertentu
     */
    public function scopePage($query, $pageName)
    {
        return $query->where('page_name', $pageName);
    }

    /**
     * Scope untuk user aktif saja
     */
    public function scopeActiveUsersOnly($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('is_active', 1);
        });
    }

    /**
     * UPDATED: Mendapatkan statistik harian dengan support role
     */
    public static function getDailyStats($date = null, $userRole = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        $query = self::activeUsersOnly()->whereDate('visited_at', $date);
        
        // Filter berdasarkan role user yang sedang login
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                page_name,
                role_name,
                COUNT(*) as total_visits,
                COUNT(DISTINCT user_id) as unique_visitors,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounce_count
            ')
            ->groupBy('page_name', 'role_name')
            ->get();
    }

    /**
     * UPDATED: Mendapatkan peak hours dengan support role
     */
    public static function getPeakHours($startDate, $endDate, $userRole = null)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                HOUR(visited_at) as hour,
                role_name,
                COUNT(*) as total_visits,
                COUNT(DISTINCT user_id) as unique_visitors
            ')
            ->groupBy('hour', 'role_name')
            ->orderBy('hour')
            ->get();
    }

    /**
     * BARU: Mendapatkan statistik berdasarkan role
     */
    public static function getRoleStats($startDate, $endDate, $userRole = null)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                role_name,
                COUNT(*) as total_visits,
                COUNT(DISTINCT user_id) as unique_visitors,
                COUNT(DISTINCT page_name) as pages_visited,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounce_count
            ')
            ->groupBy('role_name')
            ->orderBy('total_visits', 'desc')
            ->get();
    }

    /**
     * UPDATED: Mendapatkan halaman populer dengan role breakdown
     */
    public static function getPopularPages($startDate, $endDate, $userRole = null, $limit = 10)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                page_name,
                page_title,
                role_name,
                COUNT(*) as total_visits,
                COUNT(DISTINCT user_id) as unique_visitors,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounce_count
            ')
            ->groupBy('page_name', 'page_title', 'role_name')
            ->orderBy('total_visits', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * UPDATED: Mendapatkan user paling aktif dengan role support
     */
    public static function getMostActiveUsers($startDate, $endDate, $userRole = null, $limit = 20)
    {
        $query = self::with('user:id,name,username,position,department')
            ->activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                user_id,
                role_name,
                COUNT(*) as total_visits,
                COUNT(DISTINCT page_name) as pages_visited,
                COUNT(DISTINCT DATE(visited_at)) as active_days,
                MAX(visited_at) as last_visit,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                SUM(COALESCE(duration_seconds, 0)) as total_duration
            ')
            ->groupBy('user_id', 'role_name')
            ->orderBy('total_visits', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * BARU: Mendapatkan page flow analysis
     */
    public static function getPageFlow($startDate, $endDate, $userRole = null)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        // Ambil urutan halaman yang dikunjungi
        $pageSequences = $query->select([
                'user_id',
                'session_id',
                'page_name',
                'visited_at',
                'role_name'
            ])
            ->orderBy('user_id')
            ->orderBy('session_id')
            ->orderBy('visited_at')
            ->get()
            ->groupBy(['user_id', 'session_id']);
        
        $flows = [];
        foreach ($pageSequences as $userId => $sessions) {
            foreach ($sessions as $sessionId => $pages) {
                $pageNames = $pages->pluck('page_name')->toArray();
                for ($i = 0; $i < count($pageNames) - 1; $i++) {
                    $from = $pageNames[$i];
                    $to = $pageNames[$i + 1];
                    $key = $from . ' → ' . $to;
                    
                    if (!isset($flows[$key])) {
                        $flows[$key] = [
                            'from' => $from,
                            'to' => $to,
                            'count' => 0,
                            'users' => []
                        ];
                    }
                    
                    $flows[$key]['count']++;
                    $flows[$key]['users'][] = $userId;
                }
            }
        }
        
        // Sort by count
        uasort($flows, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        return array_slice($flows, 0, 20); // Top 20 flows
    }

    /**
     * Update durasi ketika user meninggalkan halaman
     */
    public function updateDuration($leftAt = null)
    {
        $leftAt = $leftAt ?: now();
        $duration = $leftAt->diffInSeconds($this->visited_at);
        
        $this->update([
            'left_at' => $leftAt,
            'duration_seconds' => $duration,
            'is_bounce' => $duration < 10 // bounce jika kurang dari 10 detik
        ]);
        
        return $this;
    }

    /**
     * Get page category untuk grouping
     */
    public function getPageCategoryAttribute()
    {
        return match($this->page_name) {
            'dashboard_viewer', 'dashboard_admin' => 'Dashboard',
            'isu_detail', 'isu_management', 'isu_create', 'isu_edit' => 'Isu',
            'trending', 'trending_management' => 'Trending',
            'documents', 'document_management' => 'Dokumen',
            'user_management' => 'User Management',
            'settings' => 'Pengaturan',
            'analytics_dashboard' => 'Analytics',
            'profile' => 'Profile',
            default => 'Lainnya'
        };
    }

    /**
     * Get role badge color
     */
    public function getRoleBadgeColorAttribute()
    {
        return match($this->role_name) {
            'admin' => 'danger',
            'editor' => 'warning',
            'verifikator1' => 'info',
            'verifikator2' => 'secondary',
            'viewer' => 'success',
            default => 'light'
        };
    }

    /**
     * Get page icon
     */
    public function getPageIconAttribute()
    {
        return match($this->page_name) {
            'dashboard_viewer', 'dashboard_admin' => 'fas fa-tachometer-alt',
            'isu_detail', 'isu_management' => 'fas fa-newspaper',
            'isu_create', 'isu_edit' => 'fas fa-edit',
            'trending', 'trending_management' => 'fas fa-fire',
            'documents', 'document_management' => 'fas fa-file-alt',
            'user_management' => 'fas fa-users',
            'settings' => 'fas fa-cog',
            'analytics_dashboard' => 'fas fa-chart-line',
            'profile' => 'fas fa-user',
            default => 'fas fa-globe'
        };
    }

    /**
     * Check if this is an admin page
     */
    public function getIsAdminPageAttribute()
    {
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
        
        return in_array($this->page_name, $adminPages);
    }

    /**
     * Clean up old analytics data
     */
    public static function cleanup($beforeDate)
    {
        return self::where('visited_at', '<', $beforeDate)->delete();
    }

    /**
     * BARU: Generate comprehensive analytics report
     */
    public static function generateReport($startDate, $endDate, $userRole = null)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('visited_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        $baseData = $query->get();
        
        return [
            'overview' => [
                'total_visits' => $baseData->count(),
                'unique_visitors' => $baseData->unique('user_id')->count(),
                'unique_sessions' => $baseData->unique('session_id')->count(),
                'total_duration' => $baseData->sum('duration_seconds'),
                'avg_duration' => $baseData->avg('duration_seconds'),
                'bounce_rate' => $baseData->where('is_bounce', true)->count() / max($baseData->count(), 1) * 100
            ],
            'by_role' => $baseData->groupBy('role_name')->map(function($items, $role) {
                return [
                    'role' => $role,
                    'visits' => $items->count(),
                    'unique_users' => $items->unique('user_id')->count(),
                    'avg_duration' => $items->avg('duration_seconds'),
                    'bounce_rate' => $items->where('is_bounce', true)->count() / max($items->count(), 1) * 100
                ];
            })->values(),
            'by_page' => $baseData->groupBy('page_name')->map(function($items, $page) {
                return [
                    'page' => $page,
                    'visits' => $items->count(),
                    'unique_visitors' => $items->unique('user_id')->count(),
                    'avg_duration' => $items->avg('duration_seconds'),
                    'bounce_rate' => $items->where('is_bounce', true)->count() / max($items->count(), 1) * 100
                ];
            })->sortByDesc('visits')->values(),
            'by_hour' => $baseData->groupBy(function($item) {
                return $item->visited_at->format('H');
            })->map(function($items, $hour) {
                return [
                    'hour' => $hour,
                    'visits' => $items->count(),
                    'unique_visitors' => $items->unique('user_id')->count()
                ];
            })->sortBy('hour')->values(),
            'top_users' => $baseData->groupBy('user_id')->map(function($items, $userId) {
                $user = $items->first()->user;
                return [
                    'user_id' => $userId,
                    'name' => $user->name ?? 'Unknown',
                    'username' => $user->username ?? 'unknown',
                    'role' => $items->first()->role_name,
                    'visits' => $items->count(),
                    'pages_visited' => $items->unique('page_name')->count(),
                    'total_duration' => $items->sum('duration_seconds'),
                    'last_visit' => $items->max('visited_at')
                ];
            })->sortByDesc('visits')->take(20)->values()
        ];
    }
}