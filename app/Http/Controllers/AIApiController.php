<?php

// GANTI SELURUH AIApiController.php dengan versi simplified ini:

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AIAnalysisResult;
use App\Models\AIUsageLog;
use App\Jobs\ProcessAIAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AIApiController extends Controller
{
    /**
     * Test endpoint
     */
    public function testEndpoint(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'AIApiController working',
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get available AI providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = [];
            
            // Static provider configuration
            $providerConfigs = [
                'groq' => [
                    'name' => 'Groq',
                    'description' => 'Cloud-based AI dengan kecepatan tinggi',
                    'icon' => 'fas fa-bolt',
                    'is_available' => !empty(config('ai.groq_api_key')),
                    'models' => config('ai.groq_models', [])
                ],
                'ollama' => [
                    'name' => 'Ollama',
                    'description' => 'Local AI models untuk privacy dan kontrol penuh', 
                    'icon' => 'fas fa-server',
                    'is_available' => $this->checkOllamaAvailability(),
                    'models' => config('ai.ollama_models', [])
                ]
            ];

            foreach ($providerConfigs as $key => $config) {
                $providers[$key] = $config;
            }

            return response()->json([
                'success' => true,
                'data' => $providers,
                'default_provider' => config('ai.default_provider', 'groq')
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
     * Test provider connection
     */
    public function testProvider(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:groq,ollama',
            'model' => 'nullable|string'
        ]);

        try {
            $provider = $request->input('provider');
            $model = $request->input('model');
            
            Log::info('Testing provider', [
                'provider' => $provider,
                'model' => $model,
                'user_id' => auth()->id()
            ]);
            
            $result = $this->testProviderConnection($provider, $model);

            return response()->json([
                'success' => $result['success'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Provider test failed', [
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test koneksi gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start AI analysis
     */
    public function analyzeUrls(Request $request): JsonResponse
    {
        $request->validate([
            'urls' => 'required|array|min:1|max:5',
            'urls.*' => 'required|url',
            'provider' => 'required|string|in:groq,ollama',
            'model' => 'nullable|string'
        ]);

        try {
            $urls = $request->input('urls');
            $provider = $request->input('provider');
            $model = $request->input('model');
            $sessionId = 'ai_' . time() . '_' . auth()->id();

            // Validate provider availability
            if (!$this->checkProviderAvailability($provider)) {
                return response()->json([
                    'success' => false,
                    'message' => "Provider {$provider} tidak tersedia saat ini"
                ], 400);
            }

            // Create initial analysis record
            $analysis = AIAnalysisResult::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'urls' => $urls,
                'processing_status' => 'pending',
                'ai_provider' => $provider,
                'ai_model' => $model ?: config("ai.{$provider}_model")
            ]);

            // Dispatch job with selected provider
            ProcessAIAnalysis::dispatch($analysis, $provider, $model);

            Log::info('AI analysis started', [
                'session_id' => $sessionId,
                'provider' => $provider,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Analisis dimulai dengan provider ' . ucfirst($provider),
                'provider_info' => [
                    'name' => $provider,
                    'model' => $model ?: config("ai.{$provider}_model")
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start AI analysis', [
                'urls' => $request->input('urls'),
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai analisis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analysis status
     */
    public function getAnalysisStatus(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }

            $progress = $this->calculateProgress($analysis);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'processing_status' => $analysis->processing_status,
                    'progress' => $progress['percentage'],
                    'current_step' => $progress['step'],
                    'created_at' => $analysis->created_at,
                    'updated_at' => $analysis->updated_at,
                    'ai_provider' => $analysis->ai_provider,
                    'ai_model' => $analysis->ai_model,
                    'error_message' => $analysis->error_message
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get analysis status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status analisis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analysis results
     */
    public function getAnalysisResult(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }

            if ($analysis->processing_status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Analisis belum selesai',
                    'status' => $analysis->processing_status
                ], 202);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'ai_resume' => $analysis->ai_resume,
                    'ai_judul_suggestions' => $analysis->ai_judul_suggestions,
                    'ai_narasi_positif' => $analysis->ai_narasi_positif,
                    'ai_narasi_negatif' => $analysis->ai_narasi_negatif,
                    'ai_tone_suggestion' => $analysis->ai_tone_suggestion,
                    'ai_skala_suggestion' => $analysis->ai_skala_suggestion,
                    'confidence_scores' => $analysis->confidence_scores,
                    'urls' => $analysis->urls,
                    'processing_time' => $analysis->processing_time,
                    'ai_provider' => $analysis->ai_provider,
                    'ai_model' => $analysis->ai_model,
                    'created_at' => $analysis->created_at,
                    'completed_at' => $analysis->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get analysis result', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil hasil analisis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel analysis
     */
    public function cancelAnalysis(string $sessionId): JsonResponse
    {
        try {
            $analysis = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }

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
     * Get recent analysis
     */
    public function getRecentAnalysis(): JsonResponse
    {
        try {
            $recent = AIAnalysisResult::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get(['id', 'session_id', 'ai_provider', 'ai_model', 'processing_status', 'created_at']);

            return response()->json([
                'success' => true,
                'data' => $recent
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get recent analysis', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat analisis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get provider models
     */
    public function getProviderModels(string $provider): JsonResponse
    {
        try {
            $models = config("ai.{$provider}_models", []);
            
            return response()->json([
                'success' => true,
                'provider' => $provider,
                'models' => $models
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get usage analytics (admin only)
     */
    public function getUsageAnalytics(): JsonResponse
    {
        try {
            $analytics = [
                'overview' => [
                    'total_analyses' => AIAnalysisResult::count(),
                    'total_users' => AIAnalysisResult::distinct('user_id')->count(),
                    'success_rate' => $this->calculateOverallSuccessRate()
                ]
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

    private function checkOllamaAvailability(): bool
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $response = $client->get(config('ai.ollama_base_url', 'http://localhost:11434') . '/api/tags');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkProviderAvailability(string $provider): bool
    {
        switch ($provider) {
            case 'groq':
                return !empty(config('ai.groq_api_key'));
            case 'ollama':
                return $this->checkOllamaAvailability();
            default:
                return false;
        }
    }

    private function testProviderConnection(string $provider, ?string $model = null): array
    {
        try {
            switch ($provider) {
                case 'groq':
                    if (class_exists('\App\Services\GroqAIService')) {
                        $service = new \App\Services\GroqAIService();
                        return $service->testConnection();
                    }
                    break;
                case 'ollama':
                    if (class_exists('\App\Services\OllamaAIService')) {
                        $service = new \App\Services\OllamaAIService();
                        return $service->testConnection();
                    }
                    break;
            }
            
            // Fallback simple test
            return [
                'success' => $this->checkProviderAvailability($provider),
                'message' => $this->checkProviderAvailability($provider) ? 
                    "Provider {$provider} tersedia" : 
                    "Provider {$provider} tidak tersedia",
                'provider' => $provider,
                'model' => $model
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test koneksi gagal: ' . $e->getMessage(),
                'provider' => $provider,
                'model' => $model
            ];
        }
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
        
        if ($analysis->processing_status === 'processing') {
            $elapsed = $analysis->created_at->diffInSeconds(now());
            $estimated = 120; // 2 minutes
            $timeProgress = min(($elapsed / $estimated) * 40, 40);
            $percentage = 50 + $timeProgress;
        }

        return [
            'percentage' => min($percentage, 100),
            'step' => $this->getCurrentStep($analysis)
        ];
    }

    private function getCurrentStep(AIAnalysisResult $analysis): string
    {
        switch ($analysis->processing_status) {
            case 'pending':
                return 'Menunggu antrian';
            case 'processing':
                return 'Menganalisis dengan AI';
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

    private function calculateOverallSuccessRate(): float
    {
        $total = AIAnalysisResult::count();
        $successful = AIAnalysisResult::where('processing_status', 'completed')->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }
}