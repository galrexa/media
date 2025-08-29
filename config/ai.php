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
    | Available Providers
    |--------------------------------------------------------------------------
    */
    
    'providers' => [
        'groq' => [
            'name' => 'Groq',
            'description' => 'Cloud-based AI dengan kecepatan tinggi',
            'icon' => 'fas fa-bolt',
            'status' => 'active'
        ],
        'ollama' => [
            'name' => 'Ollama',
            'description' => 'Local AI models untuk privacy dan kontrol penuh',
            'icon' => 'fas fa-server',
            'status' => 'active'
        ]
    ],

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
    | Ollama Configuration
    |--------------------------------------------------------------------------
    */
    
    'ollama_base_url' => env('AI_OLLAMA_BASE_URL', 'http://localhost:11434'),
    'ollama_model' => env('AI_OLLAMA_MODEL', 'gemma3:12b'),
    'ollama_timeout' => env('AI_OLLAMA_TIMEOUT', 120), // Longer timeout for local processing

    /*
    |--------------------------------------------------------------------------
    | Available Models per Provider
    |--------------------------------------------------------------------------
    */
    
    'groq_models' => [
        'gemma2-9b-it' => 'Gemma2 9B (Recommended)',
        'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Powerful)',
        'llama-3.1-70b-versatile' => 'Llama 3.1 70B',
        'mixtral-8x7b-32768' => 'Mixtral 8x7B'
    ],
    
    'ollama_models' => [
        'gemma3:12b' => 'gemma3 12B (Recommended)',
        'gemma3:4b' => 'gemma3 4B (Fastest)',
        'lamma3.1:8b' => 'Lamma3.1 8B (Fast)'
    ],

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
        
        'resume_instruction' => 'Buatlah resume berita yang komprehensif dengan struktur: Latar belakang → Inti berita → Dampak/implikasi. Gunakan maksimal 200 kata, bahasa Indonesia formal, fokus pada fakta objektif.',
        
        'title_instruction' => 'Buatlah 5 judul isu strategis yang informatif dan menarik, panjang maksimal 10 kata, cocok untuk laporan media monitoring.',
        
        'positive_narrative_instruction' => 'Berdasarkan resume berita, jika memang ada buatlah narasi positif 150-200 kata yang fokus pada aspek baik, manfaat untuk masyarakat, dan potensi keberhasilan program/kebijakan.',
        
        'negative_narrative_instruction' => 'Berdasarkan resume berita, jika memang ada buatlah narasi negatif 150-200 kata yang fokus pada risiko, tantangan, kritik, dan potensi dampak negatif secara objektif.',
        
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
    
    'rate_limit' => [
        'requests_per_minute' => env('AI_RATE_LIMIT_RPM', 60),
        'requests_per_hour' => env('AI_RATE_LIMIT_RPH', 1000),
    ],
];