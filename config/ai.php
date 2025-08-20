<?php

// config/ai.php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Features Configuration
    |--------------------------------------------------------------------------
    */
    
    'enabled' => env('AI_ENABLED', true),
    'debug_mode' => env('AI_DEBUG_MODE', false),
    'log_requests' => env('AI_LOG_REQUESTS', true),
    
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    */
    
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'groq'),
    
    /*
    |--------------------------------------------------------------------------
    | Groq Configuration - CORRECTED sesuai dokumentasi
    |--------------------------------------------------------------------------
    */
    
    'groq_api_key' => env('AI_GROQ_API_KEY'),
    'groq_base_url' => env('AI_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), // Sesuai curl docs
    'groq_model' => env('AI_GROQ_MODEL', 'gemma2-9b-it'), // Model dari curl docs
    
    /*
    |--------------------------------------------------------------------------
    | Processing Limits
    |--------------------------------------------------------------------------
    */
    
    'max_urls_per_request' => env('AI_MAX_URLS_PER_REQUEST', 5),
    'timeout_seconds' => env('AI_TIMEOUT_SECONDS', 60),
    'max_content_length' => env('AI_MAX_CONTENT_LENGTH', 30000),
    'retry_attempts' => env('AI_RETRY_ATTEMPTS', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    
    'concurrent_requests' => env('AI_CONCURRENT_REQUESTS', 3),
    'cache_results_hours' => env('AI_CACHE_RESULTS_HOURS', 24),
    'queue_enabled' => env('AI_QUEUE_ENABLED', false),
    
    /*
    |--------------------------------------------------------------------------
    | Cost Management
    |--------------------------------------------------------------------------
    */
    
    'daily_budget_usd' => env('AI_DAILY_BUDGET_USD', 10.00),
    'cost_alert_threshold' => env('AI_COST_ALERT_THRESHOLD', 80),
    
    /*
    |--------------------------------------------------------------------------
    | Available Models
    |--------------------------------------------------------------------------
    */
    
    'available_models' => [
        'groq' => [
            'llama-3.3-70b-versatile' => [
                'name' => 'Llama 3.3 70B Versatile',
                'description' => 'Latest and most capable model',
                'context_length' => 32768,
                'cost_per_1m_input' => 0.59,
                'cost_per_1m_output' => 0.79
            ],
            'gemma2-9b-it' => [
                'name' => 'Llama 3.1 8B Instant',
                'description' => 'Fast and efficient model',
                'context_length' => 8192,
                'cost_per_1m_input' => 0.05,
                'cost_per_1m_output' => 0.08
            ],
            'llama-3.1-70b-versatile' => [
                'name' => 'Llama 3.1 70B Versatile',
                'description' => 'Previous generation 70B model',
                'context_length' => 32768,
                'cost_per_1m_input' => 0.59,
                'cost_per_1m_output' => 0.79
            ],
            'mixtral-8x7b-32768' => [
                'name' => 'Mixtral 8x7B',
                'description' => 'Mixtral model for specialized tasks',
                'context_length' => 32768,
                'cost_per_1m_input' => 0.24,
                'cost_per_1m_output' => 0.24
            ],
            'gemma2-9b-it' => [
                'name' => 'Gemma 2 9B IT',
                'description' => 'Google Gemma instruction-tuned',
                'context_length' => 8192,
                'cost_per_1m_input' => 0.20,
                'cost_per_1m_output' => 0.20
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Analysis Modes
    |--------------------------------------------------------------------------
    */
    
    'analysis_modes' => [
        'fast' => [
            'model' => 'llama3-8b-8192',
            'temperature' => 0.3,
            'max_tokens' => 1000,
            'description' => 'Cepat tapi akurasi standar'
        ],
        'balanced' => [
            'model' => 'gemma2-9b-it',
            'temperature' => 0.4,
            'max_tokens' => 1500,
            'description' => 'Seimbang antara kecepatan dan akurasi'
        ],
        'accurate' => [
            'model' => 'gemma2-9b-it',
            'temperature' => 0.2,
            'max_tokens' => 2000,
            'description' => 'Akurasi tinggi tapi lebih lambat'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Content Validation Rules
    |--------------------------------------------------------------------------
    */
    
    'content_validation' => [
        'min_word_count' => 100,
        'min_suitable_score' => 40,
        'required_indonesian_words' => [
            'dan', 'yang', 'di', 'dengan', 'untuk', 'pada', 'dari', 'ke', 'dalam', 'oleh',
            'akan', 'adalah', 'sebagai', 'ini', 'itu', 'juga', 'dapat', 'telah', 'sudah'
        ],
        'news_keywords' => [
            'berita', 'laporan', 'mengatakan', 'menyatakan', 'dilaporkan', 
            'mengumumkan', 'pemerintah', 'presiden', 'menteri'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Prompt Templates
    |--------------------------------------------------------------------------
    */
    
    'prompts' => [
        'system_role' => 'Anda adalah asisten AI profesional untuk media monitoring pemerintah Indonesia yang ahli dalam analisis berita dan pembuatan konten strategis.',
        
        'resume_instruction' => 'Buatlah resume berita yang komprehensif dengan struktur: Latar belakang → Inti berita → Dampak/implikasi. Gunakan 250-300 kata, bahasa Indonesia formal, fokus pada fakta objektif.',
        
        'title_instruction' => 'Buatlah 5 judul isu strategis yang informatif dan menarik, panjang 60-100 karakter, cocok untuk laporan media monitoring.',
        
        'positive_narrative_instruction' => 'Buatlah narasi positif 150-200 kata yang fokus pada aspek baik, manfaat untuk masyarakat, dan potensi keberhasilan program/kebijakan.',
        
        'negative_narrative_instruction' => 'Buatlah narasi negatif 150-200 kata yang fokus pada risiko, tantangan, kritik, dan potensi dampak negatif secara objektif.',
        
        'tone_instruction' => 'Analisis tone berita dan jawab dengan SATU KATA: POSITIF (hal baik/kemajuan), NEGATIF (masalah/kritik), atau NETRAL (faktual/seimbang).',
        
        'scale_instruction' => 'Analisis skala dampak isu dan jawab dengan SATU KATA: TINGGI (isu nasional/dampak luas), SEDANG (regional/signifikan), atau RENDAH (lokal/minimal).'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    
    'monitoring' => [
        'track_response_times' => true,
        'track_token_usage' => true,
        'track_error_rates' => true,
        'alert_on_high_costs' => true,
        'alert_on_failures' => true,
        'cleanup_old_logs_days' => 30
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    
    'rate_limits' => [
        'requests_per_minute' => 20,
        'requests_per_hour' => 100,
        'requests_per_day' => 500,
        'tokens_per_day' => 1000000
    ]
];