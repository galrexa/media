<?php

// app/Services/GroqAIService.php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\AIUsageLog;

class GroqAIService
{
    private $client;
    private $apiKey;
    private $baseUrl;
    private $model;
    private $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.groq_api_key');
        
        // FIXED: Use proper base URL
        $this->baseUrl = 'https://api.groq.com';
        
        $this->model = config('ai.groq_model', 'llama-3.3-70b-versatile');
        $this->timeout = config('ai.timeout_seconds', 60);

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'verify' => false, // Disable SSL verification untuk development
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
            
            Log::info('AI analysis completed', [
                'user_id' => $userId,
                'processing_time' => $processingTime . 'ms',
                'model' => $this->model,
                'content_length' => strlen($combinedContent['full_content'])
            ]);
            
            return [
                'success' => true,
                'results' => $results,
                'processing_time' => $processingTime,
                'model_used' => $this->model,
                'provider' => 'groq'
            ];
            
        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'model' => $this->model
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'groq'
            ];
        }
    }

    /**
     * Combine extracted content from multiple URLs
     */
    private function combineExtractedContent(array $extractedContents): array
    {
        $titles = [];
        $contents = [];
        $authors = [];
        $dates = [];
        $domains = [];
        
        foreach ($extractedContents as $content) {
            if ($content['success']) {
                $titles[] = $content['title'] ?? '';
                $contents[] = $content['cleaned_content'] ?? '';
                $authors[] = $content['author'] ?? '';
                $dates[] = $content['publish_date'] ?? '';
                $domains[] = $content['domain'] ?? '';
            }
        }
        
        return [
            'titles' => array_filter($titles),
            'full_content' => implode("\n\n---\n\n", array_filter($contents)),
            'authors' => array_filter($authors),
            'publish_dates' => array_filter($dates),
            'domains' => array_unique(array_filter($domains)),
            'total_word_count' => str_word_count(implode(' ', array_filter($contents)))
        ];
    }

    /**
     * Generate news summary/resume
     */
    public function generateResume(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildResumePrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 800,
            'temperature' => 0.3
        ]);
        
        $this->logUsage($userId, 'resume_generation', $response);
        
        return $this->extractContentFromResponse($response);
    }

    /**
     * Generate title suggestions
     */
    public function generateTitleSuggestions(array $combinedContent, int $userId): array
    {
        $prompt = $this->buildTitlePrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 400,
            'temperature' => 0.5
        ]);
        
        $this->logUsage($userId, 'title_generation', $response);
        
        $content = $this->extractContentFromResponse($response);
        return $this->parseTitleSuggestions($content);
    }

    /**
     * Generate positive narrative
     */
    public function generatePositiveNarrative(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildPositiveNarrativePrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 600,
            'temperature' => 0.4
        ]);
        
        $this->logUsage($userId, 'positive_narrative', $response);
        
        return $this->extractContentFromResponse($response);
    }

    /**
     * Generate negative narrative
     */
    public function generateNegativeNarrative(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildNegativeNarrativePrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 600,
            'temperature' => 0.4
        ]);
        
        $this->logUsage($userId, 'negative_narrative', $response);
        
        return $this->extractContentFromResponse($response);
    }

    /**
     * Analyze tone of the news
     */
    public function analyzeTone(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildToneAnalysisPrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 100,
            'temperature' => 0.1
        ]);
        
        $this->logUsage($userId, 'tone_analysis', $response);
        
        $result = $this->extractContentFromResponse($response);
        return $this->parseToneResult($result);
    }

    /**
     * Analyze scale/impact of the issue
     */
    public function analyzeScale(array $combinedContent, int $userId): string
    {
        $prompt = $this->buildScaleAnalysisPrompt($combinedContent);
        
        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 100,
            'temperature' => 0.1
        ]);
        
        $this->logUsage($userId, 'scale_analysis', $response);
        
        $result = $this->extractContentFromResponse($response);
        return $this->parseScaleResult($result);
    }

    /**
     * Build prompt for resume generation
     */
    private function buildResumePrompt(array $content): string
    {
        return "Anda adalah seorang jurnalis profesional Indonesia yang ahli dalam merangkum berita. 

TUGAS: Buatlah resume berita yang komprehensif berdasarkan konten berita berikut.

KONTEN BERITA:
{$content['full_content']}

PANDUAN PENULISAN:
1. Gunakan bahasa Indonesia yang formal dan jelas
2. Panjang resume: 250-300 kata
3. Struktur: Latar belakang → Inti berita → Dampak/implikasi
4. Fokus pada fakta-fakta penting dan relevan
5. Hindari opini pribadi, fokus pada informasi objektif
6. Gunakan gaya penulisan media monitoring profesional

RESUME BERITA:";
    }

    /**
     * Build prompt for title suggestions
     */
    private function buildTitlePrompt(array $content): string
    {
        return "Anda adalah editor berita berpengalaman yang membuat judul isu strategis untuk media monitoring.

TUGAS: Buatlah 5 judul isu strategis berdasarkan konten berita berikut.

KONTEN BERITA:
{$content['full_content']}

KRITERIA JUDUL:
1. Panjang: 60-100 karakter
2. Menarik dan informatif
3. Mencerminkan inti berita secara akurat
4. Cocok untuk laporan media monitoring
5. Hindari sensasionalisme berlebihan
6. Gunakan kata kunci yang relevan

FORMAT JAWABAN (berikan 5 judul, masing-masing di baris terpisah dengan nomor):
1. [Judul pertama]
2. [Judul kedua]
3. [Judul ketiga]
4. [Judul keempat]
5. [Judul kelima]

SARAN JUDUL:";
    }

    /**
     * Build prompt for positive narrative
     */
    private function buildPositiveNarrativePrompt(array $content): string
    {
        return "Anda adalah analis kebijakan yang membuat narasi positif untuk media monitoring pemerintah.

TUGAS: Buatlah narasi positif berdasarkan berita berikut.

KONTEN BERITA:
{$content['full_content']}

PANDUAN NARASI POSITIF:
1. Panjang: 150-200 kata
2. Fokus pada aspek-aspek positif dan manfaat
3. Soroti dampak baik bagi masyarakat
4. Hindari berlebihan atau tidak faktual
5. Gunakan bahasa yang objektif namun optimis
6. Sebutkan potensi keberhasilan program/kebijakan

NARASI POSITIF:";
    }

    /**
     * Build prompt for negative narrative
     */
    private function buildNegativeNarrativePrompt(array $content): string
    {
        return "Anda adalah analis risiko yang membuat narasi negatif untuk media monitoring pemerintah.

TUGAS: Buatlah narasi negatif berdasarkan berita berikut.

KONTEN BERITA:
{$content['full_content']}

PANDUAN NARASI NEGATIF:
1. Panjang: 150-200 kata
2. Fokus pada risiko, tantangan, dan kritik
3. Soroti potensi dampak negatif atau masalah
4. Tetap objektif dan berdasarkan fakta
5. Hindari spekulasi berlebihan
6. Sebutkan kekhawatiran yang wajar

NARASI NEGATIF:";
    }

    /**
     * Build prompt for tone analysis
     */
    private function buildToneAnalysisPrompt(array $content): string
    {
        return "Analisis tone/sentimen berita berikut dan tentukan apakah berita ini memiliki tone POSITIF, NEGATIF, atau NETRAL.

KONTEN BERITA:
{$content['full_content']}

KRITERIA:
- POSITIF: Berita yang menggambarkan hal-hal baik, keberhasilan, kemajuan
- NEGATIF: Berita yang menggambarkan masalah, kegagalan, kritik
- NETRAL: Berita yang seimbang atau hanya menyampaikan informasi faktual

Jawab dengan SATU KATA saja: POSITIF, NEGATIF, atau NETRAL

TONE:";
    }

    /**
     * Build prompt for scale analysis
     */
    private function buildScaleAnalysisPrompt(array $content): string
    {
        return "Analisis skala dampak isu dari berita berikut dan tentukan apakah ini termasuk isu skala RENDAH, SEDANG, atau TINGGI.

KONTEN BERITA:
{$content['full_content']}

KRITERIA SKALA:
- TINGGI: Isu nasional, kebijakan besar, dampak luas ke masyarakat
- SEDANG: Isu regional/sektoral, dampak terbatas tapi signifikan
- RENDAH: Isu lokal/teknis, dampak minimal atau spesifik

Jawab dengan SATU KATA saja: RENDAH, SEDANG, atau TINGGI

SKALA:";
    }

    /**
     * Call Groq API with retry mechanism - FIXED ENDPOINT
     */
    private function callGroqAPI(string $prompt, array $options = []): array
    {
        $defaultOptions = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.3,
            'top_p' => 1,
            'stream' => false
        ];

        $requestData = array_merge($defaultOptions, $options);
        
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $startTime = microtime(true);
                
                $response = $this->client->post('/openai/v1/chat/completions', [
                    'json' => $requestData
            ]);
                
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $responseData = json_decode($response->getBody()->getContents(), true);
                
                if (!$responseData || !isset($responseData['choices'][0]['message']['content'])) {
                    throw new \Exception('Invalid response format from Groq API: ' . json_encode($responseData));
                }
                
                // Add metadata to response
                $responseData['response_time'] = $responseTime;
                $responseData['attempt'] = $attempt;
                $responseData['model_used'] = $this->model;
                
                return $responseData;
                
            } catch (RequestException $e) {
                $responseBody = '';
                if ($e->hasResponse()) {
                    $responseBody = $e->getResponse()->getBody()->getContents();
                }
                
                Log::warning("Groq API attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'status_code' => $e->getCode(),
                    'response_body' => $responseBody,
                    'request_model' => $this->model,
                    'full_url' => $this->baseUrl . '/chat/completions' // For debugging
                ]);
                
                if ($attempt === $maxRetries) {
                    throw new \Exception("Groq API failed after {$maxRetries} attempts: " . $e->getMessage() . " | Full URL: " . $this->baseUrl . "/chat/completions | Response: " . $responseBody);
                }
                
                sleep($retryDelay * $attempt); // Exponential backoff
            } catch (\Exception $e) {
                Log::error("Groq API error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'model' => $this->model,
                    'base_url' => $this->baseUrl
                ]);
                
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                
                sleep($retryDelay);
            }
        }
    }

    /**
     * Extract content from API response
     */
    private function extractContentFromResponse(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse title suggestions from response
     */
    private function parseTitleSuggestions(string $content): array
    {
        $lines = explode("\n", $content);
        $titles = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Match numbered lines like "1. Title" or "1) Title"
            if (preg_match('/^\d+[\.)]\s*(.+)/', $line, $matches)) {
                $titles[] = trim($matches[1]);
            } elseif (!empty($line) && !preg_match('/^(saran|judul|title)/i', $line)) {
                // Also capture lines that might be titles without numbers
                $titles[] = $line;
            }
        }
        
        // Ensure we have at least 5 titles, generate more if needed
        while (count($titles) < 5) {
            $titles[] = "Judul Alternatif " . (count($titles) + 1);
        }
        
        return array_slice($titles, 0, 5); // Return only first 5
    }

    /**
     * Parse tone analysis result
     */
    private function parseToneResult(string $result): string
    {
        $result = strtolower(trim($result));
        
        if (strpos($result, 'positif') !== false) {
            return 'positif';
        } elseif (strpos($result, 'negatif') !== false) {
            return 'negatif';
        } elseif (strpos($result, 'netral') !== false) {
            return 'netral';
        }
        
        // Default fallback
        return 'netral';
    }

    /**
     * Parse scale analysis result
     */
    private function parseScaleResult(string $result): string
    {
        $result = strtolower(trim($result));
        
        if (strpos($result, 'tinggi') !== false) {
            return 'tinggi';
        } elseif (strpos($result, 'rendah') !== false) {
            return 'rendah';
        } elseif (strpos($result, 'sedang') !== false) {
            return 'sedang';
        }
        
        // Default fallback
        return 'sedang';
    }

    /**
     * Calculate confidence scores for results
     */
    private function calculateConfidenceScores(array $results, array $combinedContent): array
    {
        $scores = [];
        
        // Base confidence on content quality and completeness
        $baseScore = 70;
        $wordCount = $combinedContent['total_word_count'];
        
        // Adjust base score based on content length
        if ($wordCount > 500) {
            $baseScore += 15;
        } elseif ($wordCount > 300) {
            $baseScore += 10;
        } elseif ($wordCount < 150) {
            $baseScore -= 20;
        }
        
        // Individual component scores
        $scores['resume'] = $this->calculateResumeConfidence($results['resume'], $baseScore);
        $scores['judul'] = $this->calculateTitleConfidence($results['judul_suggestions'], $baseScore);
        $scores['narasi_positif'] = $this->calculateNarrativeConfidence($results['narasi_positif'], $baseScore);
        $scores['narasi_negatif'] = $this->calculateNarrativeConfidence($results['narasi_negatif'], $baseScore);
        $scores['tone'] = $this->calculateClassificationConfidence($results['tone_analysis'], $baseScore);
        $scores['skala'] = $this->calculateClassificationConfidence($results['skala_analysis'], $baseScore);
        
        return $scores;
    }

    private function calculateResumeConfidence(string $resume, int $baseScore): int
    {
        $wordCount = str_word_count($resume);
        $score = $baseScore;
        
        // Check length
        if ($wordCount >= 200 && $wordCount <= 350) {
            $score += 10;
        } elseif ($wordCount < 150) {
            $score -= 15;
        }
        
        // Check structure and content quality
        if (strpos($resume, '.') !== false) { // Has sentences
            $score += 5;
        }
        
        return min(100, max(0, $score));
    }

    private function calculateTitleConfidence(array $titles, int $baseScore): int
    {
        $score = $baseScore;
        
        // Check if we have multiple titles
        if (count($titles) >= 5) {
            $score += 10;
        } elseif (count($titles) < 3) {
            $score -= 15;
        }
        
        // Check title length and quality
        $validTitles = 0;
        foreach ($titles as $title) {
            $length = strlen($title);
            if ($length >= 40 && $length <= 120) {
                $validTitles++;
            }
        }
        
        if ($validTitles >= 3) {
            $score += 5;
        }
        
        return min(100, max(0, $score));
    }

    private function calculateNarrativeConfidence(string $narrative, int $baseScore): int
    {
        $wordCount = str_word_count($narrative);
        $score = $baseScore;
        
        // Check length
        if ($wordCount >= 120 && $wordCount <= 250) {
            $score += 10;
        } elseif ($wordCount < 80) {
            $score -= 20;
        }
        
        return min(100, max(0, $score));
    }

    private function calculateClassificationConfidence(string $classification, int $baseScore): int
    {
        $score = $baseScore;
        
        // Classification results are usually more confident
        $score += 15;
        
        return min(100, max(0, $score));
    }

    /**
     * Log AI usage for monitoring and billing
     */
    private function logUsage(int $userId, string $operation, array $response): void
    {
        try {
            $usage = $response['usage'] ?? [];
            
            AIUsageLog::create([
                'user_id' => $userId,
                'urls_count' => 1, // This will be updated by the main service
                'processing_status' => 'success',
                'ai_provider' => 'groq',
                'ai_model' => $this->model,
                'tokens_used' => $usage['total_tokens'] ?? null,
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'cost_estimation' => $this->calculateCost($usage),
                'response_time' => $response['response_time'] ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to log AI usage', [
                'user_id' => $userId,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate estimated cost for Groq API usage
     */
    private function calculateCost(array $usage): ?float
    {
        if (!isset($usage['total_tokens'])) {
            return null;
        }
        
        // Groq pricing (as of 2024) - very affordable!
        // Llama3-70B: $0.59 per 1M input tokens, $0.79 per 1M output tokens
        // Llama3-8B: $0.05 per 1M input tokens, $0.08 per 1M output tokens
        
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;
        
        if ($this->model === 'llama-3.1-8b-instant') {
            $inputCost = ($inputTokens / 1000000) * 0.59;
            $outputCost = ($outputTokens / 1000000) * 0.79;
        } elseif ($this->model === 'llama3-8b-8192') {
            $inputCost = ($inputTokens / 1000000) * 0.05;
            $outputCost = ($outputTokens / 1000000) * 0.08;
        } else {
            // Default to Llama3-70B pricing
            $inputCost = ($inputTokens / 1000000) * 0.59;
            $outputCost = ($outputTokens / 1000000) * 0.79;
        }
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Generate more title suggestions
     */
    public function generateMoreTitles(array $combinedContent, int $userId): array
    {
        $prompt = "Anda adalah editor kreatif yang membuat variasi judul berita yang menarik.

TUGAS: Buatlah 5 judul isu strategis BARU dengan pendekatan yang berbeda dari sebelumnya.

KONTEN BERITA:
{$combinedContent['full_content']}

VARIASI GAYA JUDUL:
1. Fokus dampak ekonomi
2. Fokus aspek sosial
3. Fokus kebijakan pemerintah
4. Fokus perspektif masyarakat
5. Fokus jangka panjang

FORMAT: Berikan 5 judul, masing-masing di baris terpisah dengan nomor.

JUDUL ALTERNATIF:";

        $response = $this->callGroqAPI($prompt, [
            'max_tokens' => 400,
            'temperature' => 0.7 // Higher creativity
        ]);

        $this->logUsage($userId, 'title_regeneration', $response);

        $content = $this->extractContentFromResponse($response);
        return $this->parseTitleSuggestions($content);
    }

    /**
     * Test API connection and model availability
     */
    public function testConnection(): array
    {
        try {
            $testPrompt = "Respons dengan 'KONEKSI BERHASIL' jika Anda menerima pesan ini.";
            
            $response = $this->callGroqAPI($testPrompt, [
                'max_tokens' => 50,
                'temperature' => 0
            ]);
            
            $content = $this->extractContentFromResponse($response);
            
            return [
                'success' => true,
                'message' => 'Koneksi ke Groq API berhasil',
                'model' => $this->model,
                'response_time' => $response['response_time'] ?? 0,
                'test_response' => trim($content)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Groq API gagal: ' . $e->getMessage(),
                'model' => $this->model
            ];
        }
    }

    /**
     * Get available models from Groq - FIXED ENDPOINT
     */
    public function getAvailableModels(): array
    {
        try {
            // CORRECTED: Since base_uri is already "https://api.groq.com/openai/v1"
            // We only need to GET "/models"
            $response = $this->client->get('/openai/v1/models');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['data'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch Groq models', [
                'error' => $e->getMessage(),
                'full_url' => $this->baseUrl . '/openai/v1/models'
            ]);
            return [];
        }
    }

    /**
     * Switch to different model
     */
    public function switchModel(string $model): bool
    {
        $availableModels = [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant', 
            'llama-3.1-70b-versatile',
            'mixtral-8x7b-32768',
            'gemma2-9b-it'
        ];
        
        if (in_array($model, $availableModels)) {
            $this->model = $model;
            Log::info('Switched to model: ' . $model);
            return true;
        }
        
        Log::warning('Attempted to switch to invalid model: ' . $model);
        return false;
    }

    /**
     * Get current model info
     */
    public function getCurrentModelInfo(): array
    {
        return [
            'model' => $this->model,
            'provider' => 'groq',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'status' => 'active'
        ];
    }

    /**
     * Batch process multiple prompts efficiently
     */
    public function batchProcess(array $prompts, int $userId): array
    {
        $results = [];
        $startTime = microtime(true);
        
        foreach ($prompts as $key => $prompt) {
            try {
                $response = $this->callGroqAPI($prompt['content'], $prompt['options'] ?? []);
                $results[$key] = [
                    'success' => true,
                    'content' => $this->extractContentFromResponse($response),
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                    'response_time' => $response['response_time'] ?? 0
                ];
                
                $this->logUsage($userId, 'batch_' . $key, $response);
                
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                Log::error("Batch process failed for {$key}", [
                    'error' => $e->getMessage(),
                    'user_id' => $userId
                ]);
            }
            
            // Small delay between requests to be respectful
            usleep(100000); // 0.1 second
        }
        
        $totalTime = round((microtime(true) - $startTime) * 1000);
        
        Log::info('Batch processing completed', [
            'total_prompts' => count($prompts),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'total_time' => $totalTime . 'ms',
            'user_id' => $userId
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_prompts' => count($prompts),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success'])),
                'total_time' => $totalTime
            ]
        ];
    }

    // ADD debug method untuk troubleshooting
    public function debugConnection(): array
    {
        return [
            'config' => [
                'api_key_set' => !empty($this->apiKey),
                'api_key_length' => strlen($this->apiKey ?? ''),
                'base_url' => $this->baseUrl,
                'model' => $this->model,
                'timeout' => $this->timeout
            ],
            'expected_urls' => [
                'chat_completions' => $this->baseUrl . '/openai/v1/chat/completions',
                'models' => $this->baseUrl . '/openai/v1/models'
            ]
        ];
    }
}