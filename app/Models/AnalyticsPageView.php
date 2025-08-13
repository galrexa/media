<?php
// app/Models/AnalyticsPageView.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsPageView extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'page_name',
        'page_title',
        'url',
        'referer',
        'duration',
        'ip_address',
        'user_agent',
        'metadata',
        'viewed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'viewed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope untuk filtering berdasarkan periode
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    // Scope untuk role viewer saja
    public function scopeViewersOnly($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereHas('role', function ($roleQuery) {
                $roleQuery->where('name', 'viewer');
            });
        });
    }
}