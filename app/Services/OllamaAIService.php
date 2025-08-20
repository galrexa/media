<?php
// app/Services/OllamaAIService.php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OllamaAIService
{
    private $client;
    private $baseUrl;
    private $model;
    private $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ai.ollama_base_url', 'http://localhost:11434');
        $this->model = config('ai.ollama_model', 'llama3.2');
        $this->timeout = config('ai.timeout_seconds', 60);
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Analisis konten menggunakan Ollama
     */
    public function analyzeContent(array $combinedContent, int $userId): array
    {
        $prompt = $this->buildAnalysisPrompt($combinedContent);
        
        $response = $this->callOllamaAPI($prompt, [
            'max_tokens' => 1500,
            'temperature' => 0.4
        ]);

        $this->logUsage($userId, 'content_analysis', $response);

        return $this->parseAnalysisResponse($response);
    }

    /**
     * Generate judul isu strategis
     */
    public function generateTitle(array $combinedContent, int $userId): array
    {
        $prompt = "Anda adalah ahli komunikasi publik. Buatlah 5 judul isu strategis yang menarik dan mudah dipahami berdasarkan konten berikut:

KONTEN: {$combinedContent['full_content']}

KRITERIA JUDUL:
- Maksimal 10 kata
- Menarik perhatian
- Mudah dipahami
- Menggambarkan inti masalah
- Sesuai untuk publikasi resmi

FORMAT: Berikan 5 judul, masing-masing di baris terpisah dengan nomor.

JUDUL ISU STRATEGIS:";

        $response = $this->callOllamaAPI($prompt, [
            'max_tokens' => 300,
            'temperature' => 0.6
        ]);

        $this->logUsage($userId, 'title_generation', $response);

        $content = $this->extractContentFromResponse($response);
        return $this->parseTitleSuggestions($content);
    }

    /**
     * Call Ollama API
     */
    private function callOllamaAPI(string $prompt, array $options = []): array
    {
        $defaultOptions = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.4,
                'top_k' => 40,
                'top_p' => 0.9,
                'num_ctx' => 8192
            ]
        ];

        $requestData = array_merge_recursive($defaultOptions, $options);
        
        $maxRetries = 3;
        $retryDelay = 1;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $startTime = microtime(true);
                
                // Ollama menggunakan endpoint /api/generate
                $response = $this->client->post('/api/generate', [
                    'json' => $requestData
                ]);
                
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $responseData = json_decode($response->getBody()->getContents(), true);
                
                if (!$responseData || !isset($responseData['response'])) {
                    throw new \Exception('Invalid response format from Ollama API');
                }
                
                $responseData['response_time'] = $responseTime;
                $responseData['attempt'] = $attempt;
                $responseData['model_used'] = $this->model;
                
                return $responseData;
                
            } catch (RequestException $e) {
                Log::warning("Ollama API attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'model' => $this->model
                ]);
                
                if ($attempt === $maxRetries) {
                    throw new \Exception("Ollama API failed after {$maxRetries} attempts: " . $e->getMessage());
                }
                
                sleep($retryDelay);
                $retryDelay *= 2;
            }
        }
    }

    /**
     * Extract content from Ollama response
     */
    private function extractContentFromResponse(array $response): string
    {
        return $response['response'] ?? '';
    }

    /**
     * Test Ollama connection
     */
    public function testConnection(): array
    {
        try {
            $testPrompt = "Respons dengan 'OLLAMA BERHASIL TERHUBUNG' jika Anda menerima pesan ini.";
            
            $response = $this->callOllamaAPI($testPrompt, [
                'options' => ['temperature' => 0]
            ]);
            
            $content = $this->extractContentFromResponse($response);
            
            return [
                'success' => true,
                'message' => 'Koneksi ke Ollama berhasil',
                'model' => $this->model,
                'base_url' => $this->baseUrl,
                'response_time' => $response['response_time'] ?? 0,
                'test_response' => trim($content)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Ollama gagal: ' . $e->getMessage(),
                'model' => $this->model,
                'base_url' => $this->baseUrl
            ];
        }
    }

    /**
     * Get available models from Ollama
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->get('/api/tags');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['models'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch Ollama models', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl
            ]);
            return [];
        }
    }

    // Helper methods sama seperti GroqAIService...
    private function logUsage($userId, $operation, $response) { /* Implementation */ }
    private function parseAnalysisResponse($response): array { /* Implementation */ }
    private function parseTitleSuggestions($content): array { /* Implementation */ }
    private function buildAnalysisPrompt($combinedContent): string { /* Implementation */ }
}