<?php
// app/Models/LoginHistory.php - Model untuk tracking login history

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginHistory extends Model
{
    use HasFactory;

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'login_type',
        'login_at',
        'logout_at',
        'ip_address',
        'user_agent',
        'session_id',
        'session_duration',
        'role_name'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'session_duration' => 'integer'
    ];

    // Tidak menggunakan timestamps default karena kita pakai login_at
    public $timestamps = false;

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope untuk filter berdasarkan jenis login
     */
    public function scopeLoginType($query, $type)
    {
        return $query->where('login_type', $type);
    }

    /**
     * Scope untuk filter berdasarkan role
     */
    public function scopeByRole($query, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $query->whereIn('role_name', $roles);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopePeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('login_at', [$startDate, $endDate]);
    }

    /**
     * Scope untuk session yang masih aktif (belum logout)
     */
    public function scopeActiveSessions($query)
    {
        return $query->whereNull('logout_at');
    }

    /**
     * Scope untuk session yang sudah selesai
     */
    public function scopeCompletedSessions($query)
    {
        return $query->whereNotNull('logout_at');
    }

    /**
     * Scope untuk filter user aktif saja
     */
    public function scopeActiveUsersOnly($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('is_active', 1);
        });
    }

    /**
     * Scope untuk akses berdasarkan role user yang sedang login
     */
    public function scopeAccessibleBy($query, $user)
    {
        if ($user->isAdmin()) {
            // Admin bisa lihat semua login history
            return $query;
        } elseif ($user->isEditor() || $user->hasRole('verifikator1') || $user->hasRole('verifikator2')) {
            // Editor dan verifikator hanya bisa lihat login history viewer
            return $query->where('role_name', 'viewer');
        } else {
            // Role lain hanya bisa lihat login history sendiri
            return $query->where('user_id', $user->id);
        }
    }

    /**
     * Get statistik login harian
     */
    public static function getDailyStats($date = null, $userRole = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        $query = self::activeUsersOnly()
            ->whereDate('login_at', $date);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                login_type,
                role_name,
                COUNT(*) as total_logins,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(COALESCE(session_duration, 0)) as avg_session_duration
            ')
            ->groupBy('login_type', 'role_name')
            ->get();
    }

    /**
     * Get statistik per jam
     */
    public static function getHourlyStats($startDate, $endDate, $userRole = null)
    {
        $query = self::activeUsersOnly()
            ->whereBetween('login_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                HOUR(login_at) as hour,
                role_name,
                COUNT(*) as total_logins,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy('hour', 'role_name')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get user yang paling aktif
     */
    public static function getMostActiveUsers($startDate, $endDate, $limit = 20, $userRole = null)
    {
        $query = self::with('user:id,name,username,position,department')
            ->activeUsersOnly()
            ->whereBetween('login_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                user_id,
                role_name,
                COUNT(*) as total_logins,
                COUNT(DISTINCT DATE(login_at)) as active_days,
                MAX(login_at) as last_login,
                AVG(COALESCE(session_duration, 0)) as avg_session_duration
            ')
            ->groupBy('user_id', 'role_name')
            ->orderBy('total_logins', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get session yang sedang aktif
     */
    public static function getActiveSessions($userRole = null)
    {
        $query = self::with('user:id,name,username,position,department')
            ->activeSessions()
            ->activeUsersOnly()
            ->where('login_at', '>=', Carbon::now()->subHours(24)); // Hanya yang login dalam 24 jam terakhir
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->orderBy('login_at', 'desc')->get();
    }

    /**
     * Get trend login bulanan
     */
    public static function getMonthlyTrend($months = 6, $userRole = null)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $query = self::activeUsersOnly()
            ->whereBetween('login_at', [$startDate, $endDate]);
        
        if ($userRole && $userRole !== 'admin') {
            $query->accessibleBy(auth()->user());
        }
        
        return $query->selectRaw('
                YEAR(login_at) as year,
                MONTH(login_at) as month,
                role_name,
                login_type,
                COUNT(*) as total_logins,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy('year', 'month', 'role_name', 'login_type')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * Clean up old login history
     */
    public static function cleanup($beforeDate)
    {
        return self::where('login_at', '<', $beforeDate)->delete();
    }

    /**
     * Get session duration in human readable format
     */
    public function getSessionDurationHumanAttribute()
    {
        if (!$this->session_duration) {
            return 'Unknown';
        }
        
        $hours = floor($this->session_duration / 3600);
        $minutes = floor(($this->session_duration % 3600) / 60);
        $seconds = $this->session_duration % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        } else {
            return "{$seconds}s";
        }
    }

    /**
     * Check if session is still active
     */
    public function getIsActiveAttribute()
    {
        return is_null($this->logout_at);
    }

    /**
     * Get login type with icon
     */
    public function getLoginTypeIconAttribute()
    {
        return match($this->login_type) {
            'web' => 'fas fa-desktop',
            'api' => 'fas fa-code',
            'api_activity' => 'fas fa-sync',
            default => 'fas fa-sign-in-alt'
        };
    }

    /**
     * Get login type label
     */
    public function getLoginTypeLabelAttribute()
    {
        return match($this->login_type) {
            'web' => 'Web Login',
            'api' => 'API Login',
            'api_activity' => 'API Activity',
            default => 'Unknown'
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
}