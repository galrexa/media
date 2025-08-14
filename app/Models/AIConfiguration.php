<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AIConfiguration extends Model
{
    use HasFactory;
    
    protected $table = 'ai_configuration';

    protected $fillable = [
        'key',
        'value',
        'category',
        'description'
    ];

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Static Helper Methods
    public static function get(string $key, $default = null)
    {
        return Cache::remember("ai_config_{$key}", 3600, function() use ($key, $default) {
            $config = static::where('key', $key)->first();
            return $config ? $config->value : $default;
        });
    }

    public static function set(string $key, $value, string $category = 'general', string $description = null)
    {
        $config = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'category' => $category,
                'description' => $description
            ]
        );

        // Clear cache
        Cache::forget("ai_config_{$key}");

        return $config;
    }

    public static function getByCategory(string $category): array
    {
        return Cache::remember("ai_config_category_{$category}", 3600, function() use ($category) {
            return static::byCategory($category)->pluck('value', 'key')->toArray();
        });
    }

    // Boot method to clear cache on model events
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($config) {
            Cache::forget("ai_config_{$config->key}");
            Cache::forget("ai_config_category_{$config->category}");
        });

        static::deleted(function ($config) {
            Cache::forget("ai_config_{$config->key}");
            Cache::forget("ai_config_category_{$config->category}");
        });
    }
}