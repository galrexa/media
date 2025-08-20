<?php

/*
|--------------------------------------------------------------------------
| OllamaApiController.php
|--------------------------------------------------------------------------
| Controller untuk handle Ollama-specific API endpoints
*/

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OllamaAIService;
use App\Services\AIProviderManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaApiController extends Controller
{
    private $ollamaService;
    private $providerManager;

    public function __construct(OllamaAIService $ollamaService, AIProviderManager $providerManager)
    {
        $this->ollamaService = $ollamaService;
        $this->providerManager = $providerManager;
    }

    /**
     * Check Ollama server status
     */
    public function status(): JsonResponse
    {
        try {
            $cacheKey = 'ollama_status_' . auth()->id();
            
            $status = Cache::remember($cacheKey, 30, function () {
                return $this->ollamaService->testConnection();
            });

            return response()->json([
                'success' => true,
                'data' => $status,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::warning('Ollama status check failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ollama tidak tersedia',
                'error' => $e->getMessage(),
                'data' => [
                    'success' => false,
                    'message' => 'Koneksi ke Ollama gagal: ' . $e->getMessage(),
                    'base_url' => config('ai.ollama_base_url', 'http://localhost:11434')
                ]
            ], 503);
        }
    }

    /**
     * Get available Ollama models
     */
    public function getModels(): JsonResponse
    {
        try {
            $cacheKey = 'ollama_models_list';
            
            $models = Cache::remember($cacheKey, 300, function () {
                return $this->ollamaService->getAvailableModels();
            });

            $formattedModels = collect($models)->map(function ($model) {
                return [
                    'name' => $model['name'] ?? 'Unknown',
                    'size' => $model['size'] ?? 0,
                    'digest' => $model['digest'] ?? '',
                    'modified_at' => $model['modified_at'] ?? null,
                    'details' => $model['details'] ?? []
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedModels,
                'total' => $formattedModels->count(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch Ollama models', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar model Ollama',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprehensive health check for Ollama
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Basic connection test
            $connectionTest = $this->ollamaService->testConnection();
            
            // Model availability test
            $models = $this->ollamaService->getAvailableModels();
            
            // Performance test (simple prompt)
            $performanceStart = microtime(true);
            $testResponse = $this->ollamaService->callOllamaAPI(
                "Respond with exactly: 'HEALTH_CHECK_OK'",
                ['options' => ['temperature' => 0]]
            );
            $performanceTime = round((microtime(true) - $performanceStart) * 1000);
            
            $totalTime = round((microtime(true) - $startTime) * 1000);
            
            $healthData = [
                'overall_status' => 'healthy',
                'connection' => [
                    'status' => $connectionTest['success'] ? 'ok' : 'failed',
                    'response_time' => $connectionTest['response_time'] ?? 0,
                    'base_url' => config('ai.ollama_base_url')
                ],
                'models' => [
                    'available' => count($models),
                    'default_model' => config('ai.ollama_model'),
                    'list' => array_slice($models, 0, 5) // First 5 models
                ],
                'performance' => [
                    'test_prompt_time' => $performanceTime,
                    'test_response' => $this->ollamaService->extractContentFromResponse($testResponse),
                    'total_health_check_time' => $totalTime
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
                ],
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $healthData
            ]);

        } catch (\Exception $e) {
            Log::error('Ollama health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'data' => [
                    'overall_status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'connection' => ['status' => 'failed'],
                    'timestamp' => now()->toISOString()
                ]
            ], 503);
        }
    }

    /**
     * Pull a new model to Ollama (Admin only)
     */
    public function pullModel(Request $request): JsonResponse
    {
        $request->validate([
            'model' => 'required|string|max:100',
            'stream' => 'boolean'
        ]);

        try {
            $model = $request->input('model');
            $stream = $request->input('stream', false);

            // Start model pull process
            $result = $this->ollamaService->pullModel($model, $stream);

            Log::info('Model pull initiated', [
                'model' => $model,
                'user_id' => auth()->id(),
                'stream' => $stream
            ]);

            return response()->json([
                'success' => true,
                'message' => "Mulai download model: {$model}",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Model pull failed', [
                'model' => $request->input('model'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal pull model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a model from Ollama (Admin only)
     */
    public function deleteModel(string $model): JsonResponse
    {
        try {
            $result = $this->ollamaService->deleteModel($model);

            Log::info('Model deleted', [
                'model' => $model,
                'user_id' => auth()->id()
            ]);

            // Clear cache
            Cache::forget('ollama_models_list');

            return response()->json([
                'success' => true,
                'message' => "Model {$model} berhasil dihapus",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Model deletion failed', [
                'model' => $model,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus model',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

