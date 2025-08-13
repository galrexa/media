<?php
// app/Models/ModelHistory.php

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
        'session_id',
        'session_duration_seconds',
        'ip_address',
        'user_agent',
        'login_method',
        'login_successful',
        'failure_reason',
        'role_name'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'login_successful' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function analytics()
    {
        return $this->hasMany(UserAnalytics::class, 'login_history_id');
    }

    /**
     * Scopes
     */
    public function scopeSuccessful($query)
    {
        return $query->where('login_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('login_successful', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('login_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('logout_at');
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('login_at', [$startDate, $endDate]);
    }

    public function scopeByRole($query, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $query->whereIn('role_name', $roles);
    }

    /**
     * Methods
     */
    public function markLogout($logoutAt = null)
    {
        $logoutAt = $logoutAt ?: now();
        $duration = Carbon::parse($this->login_at)->diffInSeconds($logoutAt);
        
        $this->update([
            'logout_at' => $logoutAt,
            'session_duration_seconds' => $duration
        ]);
        
        return $this;
    }

    public function getDurationAttribute()
    {
        if ($this->logout_at) {
            return Carbon::parse($this->login_at)->diffInSeconds($this->logout_at);
        }
        
        return Carbon::parse($this->login_at)->diffInSeconds(now());
    }

    public function getIsActiveAttribute()
    {
        return is_null($this->logout_at);
    }

    /**
     * Static Methods for Analytics
     */
    public static function getLoginStats($startDate, $endDate, $userRole = null)
    {
        $query = self::successful()
            ->inPeriod($startDate, $endDate);
        
        if ($userRole && $userRole !== 'admin') {
            // Non-admin only see viewer logins
            $query->where('role_name', 'viewer');
        }
        
        return $query->selectRaw('
                login_type,
                COUNT(*) as total_logins,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(session_duration_seconds) as avg_duration,
                DATE(login_at) as login_date
            ')
            ->groupBy('login_type', 'login_date')
            ->orderBy('login_date')
            ->get();
    }

    public static function getActiveSessionsCount($minutesAgo = 30)
    {
        return self::active()
            ->where('login_at', '>=', Carbon::now()->subMinutes($minutesAgo))
            ->count();
    }

    public static function getFailedLoginAttempts($startDate, $endDate, $userRole = null)
    {
        $query = self::failed()
            ->inPeriod($startDate, $endDate);
        
        if ($userRole && $userRole !== 'admin') {
            $query->where('role_name', 'viewer');
        }
        
        return $query->selectRaw('
                user_id,
                COUNT(*) as failed_attempts,
                MAX(login_at) as last_attempt
            ')
            ->groupBy('user_id')
            ->having('failed_attempts', '>', 2)
            ->orderBy('failed_attempts', 'desc')
            ->get();
    }

    /**
     * Create login record with user context
     */
    public static function recordLogin($user, $type = 'web', $successful = true, $failureReason = null)
    {
        return self::create([
            'user_id' => $user->id,
            'login_type' => $type,
            'login_at' => now(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_method' => $type === 'api' ? 'api_key' : 'password',
            'login_successful' => $successful,
            'failure_reason' => $failureReason,
            'role_name' => $user->getHighestRoleName() ?? 'viewer'
        ]);
    }
}