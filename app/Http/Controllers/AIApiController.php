<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIProviderManager;
use App\Services\WebScrapingService;
use App\Services\AIAnalysisService;
use App\Models\AIAnalysisResult;
use App\Models\AIUsageLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AIApiController extends Controller
{
    private $providerManager;
    private $webScrapingService;
    private $aiAnalysisService;

    public function __construct(
        AIProviderManager $providerManager,
        WebScrapingService $webScrapingService,
        AIAnalysisService $aiAnalysisService
    ) {
        $this->providerManager = $providerManager;
        $this->webScrapingService = $webScrapingService;
        $this->aiAnalysisService = $aiAnalysisService;
    }

    /**
     * Get available AI providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $status = $this->providerManager->getProviderStatus();
            $providerDetails = [];

            foreach (config('ai.providers', []) as $name => $config) {
                if ($config['enabled']) {
                    $providerDetails[$name] = [
                        'name' => $name,
                        'enabled' => $config['enabled'],
                        'priority' => $config['priority'],
                        'status' => 'checking...', // Will be updated by separate status calls
                        'description' => $this->getProviderDescription($name),
                        'models' => $this->getProviderModels($name)
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'default_provider' => $status['default_provider'],
                    'available_providers' => $status['available_providers'],
                    'total_providers' => $status['total_providers'],
                    'providers' => $providerDetails
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get providers', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check specific provider status
     */
    public function checkProviderStatus(string $provider): JsonResponse
    {
        try {
            $cacheKey = "provider_status_{$provider}_" . auth()->id();
            
            $status = Cache::remember($cacheKey, 60, function () use ($provider) {
                return $this->providerManager->testProvider($provider);
            });

            return response()->json([
                'success' => true,
                'provider' => $provider,
                'data' => $status,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::warning("Provider {$provider} status check failed", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'provider' => $provider,
                'message' => "Provider {$provider} tidak tersedia",
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Test provider connection (Admin/Editor only)
     */
    public function testProvider(string $provider): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $result = $this->providerManager->testProvider($provider);
            
            $testTime = round((microtime(true) - $startTime) * 1000);

            Log::info("Provider {$provider} test completed", [
                'result' => $result,
                'test_time' => $testTime,
                'user_id' => auth()->id()
            ]);

            // Clear cache to force fresh status
            Cache::forget("provider_status_{$provider}_" . auth()->id());

            return response()->json([
                'success' => true,
                'provider' => $provider,
                'test_time' => $testTime,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Provider {$provider} test failed", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'provider' => $provider,
                'message' => "Test provider {$provider} gagal",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Switch default provider (Admin only)
     */
    public function switchProvider(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:groq,ollama,openai,claude'
        ]);

        try {
            $provider = $request->input('provider');
            
            // Test provider before switching
            $testResult = $this->providerManager->testProvider($provider);
            
            if (!$testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot switch to {$provider}: Provider test failed",
                    'test_result' => $testResult
                ], 400);
            }

            // Update configuration
            config(['ai.default_provider' => $provider]);
            
            // TODO: Save to database or config file for persistence
            
            Log::info("Default provider switched", [
                'old_provider' => config('ai.default_provider'),
                'new_provider' => $provider,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Default provider berhasil diubah ke {$provider}",
                'provider' => $provider,
                'test_result' => $testResult
            ]);

        } catch (\Exception $e) {
            Log::error('Provider switch failed', [
                'requested_provider' => $request->input('provider'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengganti provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate URLs for analysis
     */
    public function validateUrls(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'urls' => 'required|array|min:1|max:5',
            'urls.*' => 'required|url|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi URL gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $urls = $request->input('urls');
            $validationResults = [];

            foreach ($urls as $index => $url) {
                $startTime = microtime(true);
                
                try {
                    $isReachable = $this->webScrapingService->validateUrl($url);
                    $metadata = $this->webScrapingService->extractMetadata($url);
                    $responseTime = round((microtime(true) - $startTime) * 1000);

                    $validationResults[] = [
                        'url' => $url,
                        'index' => $index,
                        'valid' => true,
                        'reachable' => $isReachable,
                        'response_time' => $responseTime,
                        'metadata' => $metadata,
                        'issues' => []
                    ];

                } catch (\Exception $e) {
                    $responseTime = round((microtime(true) - $startTime) * 1000);
                    
                    $validationResults[] = [
                        'url' => $url,
                        'index' => $index,
                        'valid' => false,
                        'reachable' => false,
                        'response_time' => $responseTime,
                        'metadata' => null,
                        'issues' => [$e->getMessage()]
                    ];
                }
            }

            $validCount = collect($validationResults)->where('valid', true)->count();
            $reachableCount = collect($validationResults)->where('reachable', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'results' => $validationResults,
                    'summary' => [
                        'total' => count($urls),
                        'valid' => $validCount,
                        'reachable' => $reachableCount,
                        'invalid' => count($urls) - $validCount
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('URL validation failed', [
                'urls' => $request->input('urls'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview content from URLs
     */
    public function previewContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'urls' => 'required|array|min:1|max:3', // Limit for preview
            'urls.*' => 'required|url|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi URL gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $urls = $request->input('urls');
            $previewResults = [];

            foreach ($urls as $index => $url) {
                try {
                    $extractionResult = $this->webScrapingService->extractContent($url);
                    
                    $previewResults[] = [
                        'url' => $url,
                        'index' => $index,
                        'success' => $extractionResult['success'],
                        'title' => $extractionResult['data']['title'] ?? 'No title',
                        'description' => $extractionResult['data']['description'] ?? 'No description',
                        'content_preview' => substr($extractionResult['data']['content'] ?? '', 0, 500) . '...',
                        'word_count' => str_word_count($extractionResult['data']['content'] ?? ''),
                        'language' => $extractionResult['data']['language'] ?? 'unknown',
                        'domain' => parse_url($url, PHP_URL_HOST),
                        'extracted_at' => now()->toISOString()
                    ];

                } catch (\Exception $e) {
                    $previewResults[] = [
                        'url' => $url,
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'title' => null,
                        'description' => null,
                        'content_preview' => null
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $previewResults
            ]);

        } catch (\Exception $e) {
            Log::error('Content preview failed', [
                'urls' => $request->input('urls'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal preview konten',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analysis progress
     */
    public function getProgress(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $progress = $this->calculateProgress($analysis);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'status' => $analysis->processing_status,
                    'progress' => $progress,
                    'created_at' => $analysis->created_at,
                    'updated_at' => $analysis->updated_at,
                    'estimated_completion' => $this->estimateCompletion($analysis)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get analysis progress', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil progress analisis',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get analysis results
     */
    public function getResults(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($analysis->processing_status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Analisis belum selesai',
                    'status' => $analysis->processing_status
                ], 202); // Accepted but not complete
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'results' => [
                        'resume' => $analysis->ai_resume,
                        'title_suggestions' => $analysis->ai_judul_suggestions,
                        'narasi_positif' => $analysis->ai_narasi_positif,
                        'narasi_negatif' => $analysis->ai_narasi_negatif,
                        'tone_suggestion' => $analysis->ai_tone_suggestion,
                        'skala_suggestion' => $analysis->ai_skala_suggestion,
                        'confidence_scores' => $analysis->confidence_scores
                    ],
                    'metadata' => [
                        'urls' => $analysis->urls,
                        'processing_time' => $analysis->processing_time,
                        'ai_provider' => $analysis->ai_provider,
                        'created_at' => $analysis->created_at,
                        'completed_at' => $analysis->updated_at
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get analysis results', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil hasil analisis',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Cancel ongoing analysis
     */
    public function cancelAnalysis(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if (!in_array($analysis->processing_status, ['pending', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analisis tidak dapat dibatalkan',
                    'status' => $analysis->processing_status
                ], 400);
            }

            $analysis->update([
                'processing_status' => 'cancelled',
                'error_message' => 'Cancelled by user'
            ]);

            Log::info('Analysis cancelled by user', [
                'session_id' => $sessionId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Analisis berhasil dibatalkan',
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel analysis', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan analisis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user usage statistics
     */
    public function getUserStats(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $now = now();
            
            $stats = [
                'today' => [
                    'analyses' => AIAnalysisResult::where('user_id', $userId)
                        ->whereDate('created_at', $now->toDateString())
                        ->count(),
                    'urls_processed' => AIAnalysisResult::where('user_id', $userId)
                        ->whereDate('created_at', $now->toDateString())
                        ->sum(\DB::raw('JSON_LENGTH(urls)')),
                    'successful' => AIAnalysisResult::where('user_id', $userId)
                        ->whereDate('created_at', $now->toDateString())
                        ->where('processing_status', 'completed')
                        ->count()
                ],
                'this_month' => [
                    'analyses' => AIAnalysisResult::where('user_id', $userId)
                        ->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year)
                        ->count(),
                    'avg_processing_time' => AIAnalysisResult::where('user_id', $userId)
                        ->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year)
                        ->where('processing_status', 'completed')
                        ->avg('processing_time')
                ],
                'total' => [
                    'analyses' => AIAnalysisResult::where('user_id', $userId)->count(),
                    'success_rate' => $this->calculateSuccessRate($userId)
                ],
                'recent_analyses' => AIAnalysisResult::where('user_id', $userId)
                    ->latest()
                    ->take(5)
                    ->select(['session_id', 'processing_status', 'created_at', 'processing_time'])
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user stats', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik pengguna',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get usage analytics (Admin only)
     */
    public function getUsageAnalytics(): JsonResponse
    {
        try {
            $analytics = [
                'overview' => [
                    'total_analyses' => AIAnalysisResult::count(),
                    'total_users' => AIAnalysisResult::distinct('user_id')->count(),
                    'success_rate' => $this->calculateOverallSuccessRate(),
                    'avg_processing_time' => AIAnalysisResult::where('processing_status', 'completed')
                        ->avg('processing_time')
                ],
                'daily_stats' => AIAnalysisResult::select(\DB::raw('DATE(created_at) as date'))
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw('SUM(CASE WHEN processing_status = "completed" THEN 1 ELSE 0 END) as successful')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'provider_stats' => AIAnalysisResult::select('ai_provider')
                    ->selectRaw('COUNT(*) as count')
                    ->selectRaw('AVG(processing_time) as avg_time')
                    ->whereNotNull('ai_provider')
                    ->groupBy('ai_provider')
                    ->get(),
                'top_users' => AIAnalysisResult::select('user_id')
                    ->selectRaw('COUNT(*) as analysis_count')
                    ->groupBy('user_id')
                    ->orderByDesc('analysis_count')
                    ->take(10)
                    ->with('user:id,name,email')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get usage analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    private function getProviderDescription(string $provider): string
    {
        $descriptions = [
            'groq' => 'Cloud-based AI service dengan kecepatan tinggi',
            'ollama' => 'Local AI models untuk privacy dan kontrol penuh',
            'openai' => 'OpenAI GPT models untuk kualitas tinggi',
            'claude' => 'Anthropic Claude untuk analisis yang mendalam'
        ];

        return $descriptions[$provider] ?? 'AI Provider';
    }

    private function getProviderModels(string $provider): array
    {
        $models = config("ai.{$provider}_models", []);
        return array_keys($models);
    }

    private function calculateProgress(AIAnalysisResult $analysis): array
    {
        $statusProgress = [
            'pending' => 10,
            'processing' => 50,
            'completed' => 100,
            'failed' => 0,
            'cancelled' => 0
        ];

        $percentage = $statusProgress[$analysis->processing_status] ?? 0;
        
        // Add time-based progress for processing status
        if ($analysis->processing_status === 'processing') {
            $elapsed = $analysis->created_at->diffInSeconds(now());
            $estimated = 120; // 2 minutes estimate
            $timeProgress = min(($elapsed / $estimated) * 40, 40); // Max 40% from time
            $percentage = 50 + $timeProgress;
        }

        return [
            'percentage' => min($percentage, 100),
            'status' => $analysis->processing_status,
            'step' => $this->getCurrentStep($analysis),
            'elapsed_time' => $analysis->created_at->diffInSeconds(now())
        ];
    }

    private function getCurrentStep(AIAnalysisResult $analysis): string
    {
        switch ($analysis->processing_status) {
            case 'pending':
                return 'Menunggu antrian';
            case 'processing':
                if (empty($analysis->extracted_content)) {
                    return 'Mengekstrak konten';
                } elseif (empty($analysis->ai_resume)) {
                    return 'Menganalisis dengan AI';
                } else {
                    return 'Menyelesaikan analisis';
                }
            case 'completed':
                return 'Selesai';
            case 'failed':
                return 'Gagal';
            case 'cancelled':
                return 'Dibatalkan';
            default:
                return 'Unknown';
        }
    }

    private function estimateCompletion(AIAnalysisResult $analysis): ?string
    {
        if ($analysis->processing_status !== 'processing') {
            return null;
        }

        $elapsed = $analysis->created_at->diffInSeconds(now());
        $remaining = max(120 - $elapsed, 30); // Minimum 30 seconds remaining
        
        return now()->addSeconds($remaining)->toISOString();
    }

    private function calculateSuccessRate(int $userId): float
    {
        $total = AIAnalysisResult::where('user_id', $userId)->count();
        $successful = AIAnalysisResult::where('user_id', $userId)
            ->where('processing_status', 'completed')
            ->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    private function calculateOverallSuccessRate(): float
    {
        $total = AIAnalysisResult::count();
        $successful = AIAnalysisResult::where('processing_status', 'completed')->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }
}