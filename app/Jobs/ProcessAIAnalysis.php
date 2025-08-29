<?php

// Ganti dengan Job ProcessAIAnalysis yang benar

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\AIAnalysisResult;
use App\Services\GroqAIService;
use App\Services\OllamaAIService;

class ProcessAIAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $analysis;
    protected $provider;
    protected $model;

    /**
     * Create a new job instance.
     */
    public function __construct(AIAnalysisResult $analysis, string $provider = 'groq', ?string $model = null)
    {
        $this->analysis = $analysis;
        $this->provider = $provider;
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting AI analysis job', [
                'session_id' => $this->analysis->session_id,
                'provider' => $this->provider,
                'model' => $this->model,
                'urls_count' => count($this->analysis->urls)
            ]);

            // Update status to processing
            $this->analysis->update([
                'processing_status' => 'processing',
                'ai_provider' => $this->provider,
                'ai_model' => $this->model ?: config("ai.{$this->provider}_model")
            ]);

            // Extract content from URLs (simplified version)
            $extractedContents = $this->extractContentFromUrls($this->analysis->urls);

            // Save extracted content
            $this->analysis->update([
                'extracted_content' => $extractedContents
            ]);

            // Get AI service based on provider
            $aiService = $this->getAIService();
            
            // Perform AI analysis
            $aiResults = $aiService->analyzeUrls($extractedContents, $this->analysis->user_id);

            // Calculate processing time
            $processingTime = round(microtime(true) - $startTime);

            // Update analysis with results
            $this->analysis->update([
                'ai_resume' => $aiResults['resume'] ?? null,
                'ai_judul_suggestions' => $aiResults['judul_suggestions'] ?? null,
                'ai_narasi_positif' => $aiResults['narasi_positif'] ?? null,
                'ai_narasi_negatif' => $aiResults['narasi_negatif'] ?? null,
                'ai_tone_suggestion' => $aiResults['tone_analysis'] ?? null,
                'ai_skala_suggestion' => $aiResults['skala_analysis'] ?? null,
                'confidence_scores' => $aiResults['confidence_scores'] ?? null,
                'processing_status' => 'completed',
                'processing_time' => $processingTime
            ]);

            Log::info('AI analysis completed successfully', [
                'session_id' => $this->analysis->session_id,
                'provider' => $this->provider,
                'processing_time' => $processingTime . 's'
            ]);

        } catch (\Exception $e) {
            $processingTime = round(microtime(true) - $startTime);
            
            Log::error('AI analysis failed', [
                'session_id' => $this->analysis->session_id,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'processing_time' => $processingTime . 's'
            ]);

            $this->analysis->update([
                'processing_status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_time' => $processingTime
            ]);

            // Re-throw the exception to mark job as failed
            throw $e;
        }
    }

    /**
     * Extract content from URLs (simplified version)
     */
    private function extractContentFromUrls(array $urls): array
    {
        $extractedContents = [];
        
        foreach ($urls as $url) {
            try {
                // Simple content extraction using file_get_contents
                $content = file_get_contents($url);
                
                if ($content !== false) {
                    // Basic HTML to text conversion
                    $cleanContent = strip_tags($content);
                    $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);
                    $cleanContent = trim($cleanContent);
                    
                    $extractedContents[] = [
                        'url' => $url,
                        'title' => $this->extractTitle($content),
                        'content' => substr($cleanContent, 0, 5000), // Limit content
                        'meta' => [
                            'length' => strlen($cleanContent),
                            'extracted_at' => now()->toISOString()
                        ]
                    ];
                } else {
                    throw new \Exception('Failed to fetch content');
                }
                
            } catch (\Exception $e) {
                Log::warning('Failed to extract content from URL', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                
                $extractedContents[] = [
                    'url' => $url,
                    'title' => '',
                    'content' => '',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $extractedContents;
    }

    /**
     * Extract title from HTML content
     */
    private function extractTitle(string $htmlContent): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $htmlContent, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        return 'No title found';
    }

    /**
     * Get appropriate AI service based on provider
     */
    private function getAIService()
    {
        switch ($this->provider) {
            case 'groq':
                return new GroqAIService();
                
            case 'ollama':
                return new OllamaAIService();
                
            default:
                throw new \InvalidArgumentException("Unsupported AI provider: {$this->provider}");
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI analysis job failed', [
            'session_id' => $this->analysis->session_id,
            'provider' => $this->provider,
            'exception' => $exception->getMessage()
        ]);

        $this->analysis->update([
            'processing_status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}