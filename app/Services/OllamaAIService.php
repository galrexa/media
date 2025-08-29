<?php

// app/Services/OllamaAIService.php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\AIUsageLog;

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
        $this->timeout = config('ai.ollama_timeout', 120);

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Analyze URLs and generate comprehensive isu content
     */
    public function analyzeUrls(array $extractedContents, int $userId): array
    {
        $startTime = microtime(true);
        
        try {
            // Combine all extracted content
            $combinedContent = $this->combineExtractedContent($extractedContents);
            
            // Generate all AI outputs
            $results = [
                'resume' => $this->generateResume($combinedContent, $userId),
                'judul_suggestions' => $this->generateTitleSuggestions($combinedContent, $userId),
                'narasi_positif' => $this->generatePositiveNarrative($combinedContent, $userId),
                'narasi_negatif' => $this->generateNegativeNarrative($combinedContent, $userId),
                'tone_analysis' => $this->analyzeTone($combinedContent, $userId),
                'skala_analysis' => $this->analyzeScale($combinedContent, $userId)
            ];
            
            // Calculate confidence scores
            $results['confidence_scores'] = $this->calculateConfidenceScores($results, $combinedContent);
            
            $processingTime = round((microtime(true) - $startTime) * 1000);
            
            Log::info('Ollama AI analysis completed', [
                'user_id' => $userId,
                'processing_time' => $processingTime . 'ms',
                'model' => $this->model,
                'provider' => 'ollama'
            ]);
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error('Ollama AI analysis failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'model' => $this->model
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate resume from combined content
     */
    private function generateResume(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildAnalysisPrompt($combinedContent);
        
        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.3,
                'top_p' => 0.9
            ]
        ]);
        
        $this->logUsage($userId, 'resume_generation', $response);
        return $this->extractContentFromResponse($response);
    }

    /**
     * Generate title suggestions
     */
    private function generateTitleSuggestions(array $combinedContent, int $userId): array
    {
        $prompt = "Anda adalah analis berita profesional. Berdasarkan konten berita berikut, buatlah 5 judul isu yang menarik dan relevan.

KONTEN BERITA:
{$combinedContent['full_content']}

PETUNJUK:
- Judul informatif dan menarik
- Maksimal 10 kata per judul
- Hindari clickbait yang berlebihan
- Gunakan bahasa Indonesia yang baik

FORMAT: Berikan 5 judul, masing-masing di baris terpisah dengan nomor.

JUDUL STRATEGIS:";

        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9
            ]
        ]);
        
        $this->logUsage($userId, 'title_generation', $response);
        $content = $this->extractContentFromResponse($response);
        return $this->parseTitleSuggestions($content);
    }

    /**
     * Generate positive narrative
     */
    private function generatePositiveNarrative(array $combinedContent, int $userId): string
    {
        $prompt = "Berdasarkan konten berita berikut, buatlah narasi positif yang objektif dan berimbang.

KONTEN BERITA:
{$combinedContent['full_content']}

TUGAS: Buatlah narasi yang menekankan aspek positif, peluang, dan solusi potensial tanpa mengabaikan realitas. Narasi harus tetap faktual dan tidak bias.

NARASI POSITIF:";

        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.5,
                'top_p' => 0.8
            ]
        ]);
        
        $this->logUsage($userId, 'positive_narrative', $response);
        return $this->extractContentFromResponse($response);
    }

    /**
     * Generate negative narrative
     */
    private function generateNegativeNarrative(array $combinedContent, int $userId): string
    {
        $prompt = "Berdasarkan konten berita berikut, buatlah narasi yang mengidentifikasi risiko, tantangan, dan dampak negatif potensial.

KONTEN BERITA:
{$combinedContent['full_content']}

TUGAS: Buatlah narasi yang objektif mengidentifikasi risiko dan tantangan tanpa menjadi sensasional. Fokus pada analisis faktual.

NARASI ANALISIS RISIKO:";

        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.5,
                'top_p' => 0.8
            ]
        ]);
        
        $this->logUsage($userId, 'negative_narrative', $response);
        return $this->extractContentFromResponse($response);
    }

    /**
     * Analyze tone suggestion
     */
    private function analyzeTone(array $combinedContent, int $userId): string
    {
        $prompt = "Berdasarkan konten berita berikut, tentukan tone yang paling sesuai untuk komunikasi publik.

KONTEN BERITA:
{$combinedContent['full_content']}

Pilihan tone:
- positif: Untuk berita yang membawa dampak baik
- negatif: Untuk berita yang memerlukan kehati-hatian atau kritik
- netral: Untuk berita yang memerlukan penyampaian objektif

Jawab dengan satu kata saja: positif, negatif, atau netral.

TONE:";

        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.1,
                'top_p' => 0.5
            ]
        ]);
        
        $this->logUsage($userId, 'tone_analysis', $response);
        $tone = trim(strtolower($this->extractContentFromResponse($response)));
        return in_array($tone, ['positif', 'negatif', 'netral']) ? $tone : 'netral';
    }

    /**
     * Analyze scale suggestion
     */
    private function analyzeScale(array $combinedContent, int $userId): string
    {
        $prompt = "Berdasarkan konten berita berikut, tentukan skala dampak atau tingkat kepentingan isu ini.

KONTEN BERITA:
{$combinedContent['full_content']}

Pilihan skala:
- rendah: Dampak terbatas, kepentingan lokal
- sedang: Dampak regional, kepentingan menengah
- tinggi: Dampak nasional/strategis, kepentingan tinggi

Jawab dengan satu kata saja: rendah, sedang, atau tinggi.

SKALA:";

        $response = $this->callOllamaAPI($prompt, [
            'options' => [
                'temperature' => 0.1,
                'top_p' => 0.5
            ]
        ]);
        
        $this->logUsage($userId, 'scale_analysis', $response);
        $scale = trim(strtolower($this->extractContentFromResponse($response)));
        return in_array($scale, ['rendah', 'sedang', 'tinggi']) ? $scale : 'sedang';
    }

    /**
     * Call Ollama API with retry mechanism
     */
    private function callOllamaAPI(string $prompt, array $options = []): array
    {
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $startTime = microtime(true);
                
                $payload = array_merge([
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false
                ], $options);
                
                $response = $this->client->post('/api/generate', [
                    'json' => $payload
                ]);
                
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $data = json_decode($response->getBody()->getContents(), true);
                
                if (!$data || !isset($data['response'])) {
                    throw new \Exception('Invalid response format from Ollama');
                }
                
                $data['response_time'] = $responseTime;
                return $data;
                
            } catch (RequestException $e) {
                Log::warning("Ollama API attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'model' => $this->model,
                    'base_url' => $this->baseUrl
                ]);
                
                if ($attempt === $maxRetries) {
                    throw new \Exception('Ollama API call failed after ' . $maxRetries . ' attempts: ' . $e->getMessage());
                }
                
                sleep($retryDelay);
                $retryDelay *= 2;
                
            } catch (\Exception $e) {
                Log::error("Ollama API error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'model' => $this->model
                ]);
                
                if ($attempt === $maxRetries) {
                    throw $e;
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

    // Helper methods (sama seperti GroqAIService)
    private function combineExtractedContent(array $extractedContents): array
    {
        $fullContent = '';
        $totalChars = 0;
        
        foreach ($extractedContents as $content) {
            if (isset($content['content'])) {
                $fullContent .= $content['content'] . "\n\n";
                $totalChars += strlen($content['content']);
            }
        }
        
        return [
            'full_content' => trim($fullContent),
            'total_characters' => $totalChars,
            'source_count' => count($extractedContents)
        ];
    }

    private function buildAnalysisPrompt(array $combinedContent): string
    {
        return "Anda adalah seorang analis isu strategis profesional. Berdasarkan konten berita berikut, buatlah resume yang komprehensif dan objektif.

KONTEN BERITA:
{$combinedContent['full_content']}

TUGAS: Buatlah resume yang mencakup:
1. Ringkasan utama isu/peristiwa
2. Latar belakang dan konteks
3. Stakeholder yang terlibat
4. Dampak dan implikasi
5. Perkembangan yang perlu diawasi

Resume harus faktual, objektif, dan mudah dipahami. Maksimal 300 kata.

RESUME ANALISIS:";
    }

    private function parseTitleSuggestions(string $content): array
    {
        $lines = explode("\n", $content);
        $titles = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                $titles[] = trim($matches[1]);
            }
        }
        
        return array_slice($titles, 0, 5);
    }

    private function calculateConfidenceScores(array $results, array $combinedContent): array
    {
        $baseScore = min(100, max(50, $combinedContent['total_characters'] / 100));
        
        return [
            'resume' => $baseScore,
            'judul_suggestions' => $baseScore - 5,
            'narasi_positif' => $baseScore - 10,
            'narasi_negatif' => $baseScore - 10,
            'tone_analysis' => $baseScore - 15,
            'skala_analysis' => $baseScore - 15,
            'overall' => $baseScore - 8
        ];
    }

    private function logUsage($userId, $operation, $response)
    {
        try {
            AIUsageLog::create([
                'user_id' => $userId,
                'urls_count' => 1,
                'processing_status' => 'success',
                'ai_provider' => 'ollama',
                'ai_model' => $this->model,
                'tokens_used' => null, // Ollama doesn't provide token count
                'response_time' => $response['response_time'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log Ollama usage', [
                'user_id' => $userId,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
        }
    }
}