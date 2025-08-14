<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIAnalysisResult extends Model
{
    use HasFactory;

    protected $table = 'ai_analysis_results';

    protected $fillable = [
        'session_id',
        'user_id',
        'urls',
        'extracted_content',
        'ai_resume',
        'ai_judul_suggestions',
        'ai_narasi_positif',
        'ai_narasi_negatif',
        'ai_tone_suggestion',
        'ai_skala_suggestion',
        'confidence_scores',
        'processing_status',
        'processing_time',
        'error_message',
        'ai_provider',
        'ai_model'
    ];

    protected $casts = [
        'urls' => 'array',
        'extracted_content' => 'array',
        'ai_judul_suggestions' => 'array',
        'confidence_scores' => 'array',
        'processing_time' => 'integer'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(AIUsageLog::class, 'analysis_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors & Mutators
    public function getAverageConfidenceAttribute()
    {
        if (!$this->confidence_scores) {
            return 0;
        }

        $scores = array_values($this->confidence_scores);
        return round(array_sum($scores) / count($scores), 2);
    }

    public function getFormattedProcessingTimeAttribute()
    {
        if (!$this->processing_time) {
            return 'N/A';
        }

        if ($this->processing_time < 60) {
            return $this->processing_time . ' detik';
        }

        return round($this->processing_time / 60, 1) . ' menit';
    }

    public function getUrlCountAttribute()
    {
        return count($this->urls ?? []);
    }

    // Helper Methods
    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    public function getHighConfidenceFields(): array
    {
        if (!$this->confidence_scores) {
            return [];
        }

        return array_filter($this->confidence_scores, function($score) {
            return $score >= 80;
        });
    }

    public function getLowConfidenceFields(): array
    {
        if (!$this->confidence_scores) {
            return [];
        }

        return array_filter($this->confidence_scores, function($score) {
            return $score < 70;
        });
    }
}
