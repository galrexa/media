<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsageLog extends Model
{
    use HasFactory;
    
    const UPDATED_AT = null; // Only track created_at
    protected $table = 'ai_usage_logs';
    protected $fillable = [
        'user_id',
        'analysis_id',
        'urls_count',
        'processing_status',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'prompt_tokens',
        'completion_tokens',
        'cost_estimation',
        'error_details',
        'response_time'
    ];

    protected $casts = [
        'error_details' => 'array',
        'cost_estimation' => 'decimal:6',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysisResult(): BelongsTo
    {
        return $this->belongsTo(AIAnalysisResult::class, 'analysis_id');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('processing_status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getFormattedCostAttribute()
    {
        return $this->cost_estimation ? '$' . number_format($this->cost_estimation, 4) : 'N/A';
    }

    public function getFormattedResponseTimeAttribute()
    {
        if (!$this->response_time) {
            return 'N/A';
        }

        if ($this->response_time < 1000) {
            return $this->response_time . 'ms';
        }

        return round($this->response_time / 1000, 2) . 's';
    }

    // Static Methods
    public static function getTotalCostToday(): float
    {
        return static::today()->sum('cost_estimation') ?? 0;
    }

    public static function getTotalCostThisMonth(): float
    {
        return static::thisMonth()->sum('cost_estimation') ?? 0;
    }

    public static function getSuccessRateToday(): float
    {
        $total = static::today()->count();
        if ($total === 0) return 0;

        $successful = static::today()->successful()->count();
        return round(($successful / $total) * 100, 2);
    }

    public static function getAverageResponseTime(): float
    {
        return static::successful()->avg('response_time') ?? 0;
    }
}
