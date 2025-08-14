<?php

// app/Services/AIAnalysisService.php

namespace App\Services;

use App\Models\AIAnalysisResult;
use App\Models\AIUsageLog;
use App\Services\WebScrapingService;
use App\Services\GroqAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIAnalysisService
{
    private $webScrapingService;
    private $groqService;
    private $maxUrls;
    private $timeout;

    public function __construct(
        WebScrapingService $webScrapingService,
        GroqAIService $groqService
    ) {
        $this->webScrapingService = $webScrapingService;
        $this->groqService = $groqService;
        $this->maxUrls = config('ai.max_urls_per_request', 5);
        $this->timeout = config('ai.timeout_seconds', 180);
    }

    /**
     * Main method to analyze URLs and generate isu content
     */
    public function analyzeUrls(array $urls, int $userId, array $options = []): string
    {
        $sessionId = $this->generateSessionId();
        
        try {
            // Validate inputs
            $this->validateInputs($urls, $userId);
            
            // Create initial analysis record
            $analysisResult = $this->createInitialAnalysisRecord($sessionId, $userId, $urls, $options);
            
            // Start background processing
            $this->processAnalysisInBackground($analysisResult);
            
            return $sessionId;
            
        } catch (\Exception $e) {
            Log::error('AI analysis initialization failed', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'urls' => $urls,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process the actual AI analysis (can be run in background)
     */
    public function processAnalysis(AIAnalysisResult $analysisResult): void
    {
        $startTime = microtime(true);
        
        try {
            // Update status to processing
            $analysisResult->update(['processing_status' => 'processing']);
            
            // Step 1: Extract content from URLs
            Log::info('Starting content extraction', ['session_id' => $analysisResult->session_id]);
            $extractionResults = $this->extractContentFromUrls($analysisResult->urls);
            
            // Update with extracted content
            $analysisResult->update(['extracted_content' => $extractionResults]);
            
            // Step 2: Check content suitability
            $suitabilityCheck = $this->checkContentSuitability($extractionResults);
            if (!$suitabilityCheck['suitable']) {
                throw new \Exception('Content not suitable for AI analysis: ' . implode(', ', $suitabilityCheck['issues']));
            }
            
            // Step 3: Perform AI analysis
            Log::info('Starting AI analysis', ['session_id' => $analysisResult->session_id]);
            $aiResults = $this->performAIAnalysis($extractionResults, $analysisResult->user_id);
            
            // Step 4: Save results
            $processingTime = round((microtime(true) - $startTime));
            
            $analysisResult->update([
                'ai_resume' => $aiResults['results']['resume'],
                'ai_judul_suggestions' => $aiResults['results']['judul_suggestions'],
                'ai_narasi_positif' => $aiResults['results']['narasi_positif'],
                'ai_narasi_negatif' => $aiResults['results']['narasi_negatif'],
                'ai_tone_suggestion' => $aiResults['results']['tone_analysis'],
                'ai_skala_suggestion' => $aiResults['results']['skala_analysis'],
                'confidence_scores' => $aiResults['results']['confidence_scores'],
                'processing_status' => 'completed',
                'processing_time' => $processingTime,
                'ai_provider' => 'groq',
                'ai_model' => $this->groqService->getCurrentModelInfo()['model']
            ]);
            
            Log::info('AI analysis completed successfully', [
                'session_id' => $analysisResult->session_id,
                'processing_time' => $processingTime . 's'
            ]);
            
        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime));
            
            $analysisResult->update([
                'processing_status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_time' => $processingTime
            ]);
            
            // Log failed usage
            AIUsageLog::create([
                'user_id' => $analysisResult->user_id,
                'analysis_id' => $analysisResult->id,
                'urls_count' => count($analysisResult->urls),
                'processing_status' => 'failed',
                'ai_provider' => 'groq',
                'error_details' => ['error' => $e->getMessage()],
                'response_time' => $processingTime * 1000
            ]);
            
            Log::error('AI analysis failed', [
                'session_id' => $analysisResult->session_id,
                'error' => $e->getMessage(),
                'processing_time' => $processingTime . 's'
            ]);
            
            throw $e;
        }
    }

    /**
     * Get analysis status for progress tracking
     */
    public function getAnalysisStatus(string $sessionId): array
    {
        $analysisResult = AIAnalysisResult::where('session_id', $sessionId)->first();
        
        if (!$analysisResult) {
            return [
                'found' => false,
                'error' => 'Session not found'
            ];
        }
        
        $progress = $this->calculateProgress($analysisResult);
        
        return [
            'found' => true,
            'session_id' => $sessionId,
            'status' => $analysisResult->processing_status,
            'progress' => $progress['percentage'],
            'current_step' => $progress['current_step'],
            'current_step_key' => $progress['step_key'],
            'completed_steps' => $progress['completed_steps'],
            'estimated_time_remaining' => $progress['time_remaining'],
            'error_message' => $analysisResult->error_message,
            'processing_time' => $analysisResult->processing_time
        ];
    }

    /**
     * Get analysis results
     */
    public function getAnalysisResults(string $sessionId): ?AIAnalysisResult
    {
        return AIAnalysisResult::where('session_id', $sessionId)
            ->with('user', 'usageLogs')
            ->first();
    }

    /**
     * Cancel ongoing analysis
     */
    public function cancelAnalysis(string $sessionId): bool
    {
        $analysisResult = AIAnalysisResult::where('session_id', $sessionId)->first();
        
        if (!$analysisResult || $analysisResult->processing_status === 'completed') {
            return false;
        }
        
        $analysisResult->update([
            'processing_status' => 'failed',
            'error_message' => 'Analysis cancelled by user'
        ]);
        
        Log::info('Analysis cancelled', ['session_id' => $sessionId]);
        
        return true;
    }

    /**
     * Generate more title suggestions
     */
    public function generateMoreTitles(string $sessionId): array
    {
        $analysisResult = AIAnalysisResult::where('session_id', $sessionId)->first();
        
        if (!$analysisResult || !$analysisResult->extracted_content) {
            throw new \Exception('Analysis result not found or content not available');
        }
        
        // Prepare combined content for AI
        $combinedContent = $this->prepareCombinedContent($analysisResult->extracted_content);
        
        // Generate new titles
        $newTitles = $this->groqService->generateMoreTitles($combinedContent, $analysisResult->user_id);
        
        return [
            'success' => true,
            'suggestions' => $newTitles,
            'session_id' => $sessionId
        ];
    }

    /**
     * Store final isu from AI results
     */
    public function storeIsuFromResults(array $data, string $sessionId): array
    {
        $analysisResult = AIAnalysisResult::where('session_id', $sessionId)->first();
        
        if (!$analysisResult || $analysisResult->processing_status !== 'completed') {
            throw new \Exception('Analysis result not found or not completed');
        }
        
        DB::beginTransaction();
        
        try {
            // Create isu record (assuming you have existing Isu model)
            $isu = \App\Models\Isu::create([
                'user_id' => $analysisResult->user_id,
                'judul' => $data['judul'],
                'resume' => $data['resume'],
                'narasi_positif' => $data['narasi_positif'],
                'narasi_negatif' => $data['narasi_negatif'],
                'tone' => $data['tone'],
                'skala' => $data['skala'],
                'sumber' => 'AI Analysis',
                'referensi_url' => $analysisResult->urls,
                'ai_generated' => true,
                'ai_session_id' => $sessionId,
                'status' => $data['send_for_verification'] ?? false ? 'pending_verification' : 'draft'
            ]);
            
            // Update analysis result to mark as used
            $analysisResult->update(['isu_id' => $isu->id]);
            
            DB::commit();
            
            Log::info('Isu created from AI analysis', [
                'isu_id' => $isu->id,
                'session_id' => $sessionId,
                'user_id' => $analysisResult->user_id
            ]);
            
            return [
                'success' => true,
                'isu_id' => $isu->id,
                'message' => 'Isu berhasil dibuat dari hasil AI',
                'redirect_url' => route('isu.show', $isu->id)
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to create isu from AI results', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Private helper methods
     */
    private function generateSessionId(): string
    {
        return 'AI-' . date('Ymd-His') . '-' . Str::random(8);
    }

    private function validateInputs(array $urls, int $userId): void
    {
        if (empty($urls)) {
            throw new \Exception('At least one URL is required');
        }
        
        if (count($urls) > $this->maxUrls) {
            throw new \Exception("Maximum {$this->maxUrls} URLs allowed per request");
        }
        
        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception("Invalid URL format: {$url}");
            }
        }
    }

    private function createInitialAnalysisRecord(string $sessionId, int $userId, array $urls, array $options): AIAnalysisResult
    {
        return AIAnalysisResult::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'urls' => $urls,
            'processing_status' => 'pending',
            'ai_provider' => 'groq'
        ]);
    }

    private function processAnalysisInBackground(AIAnalysisResult $analysisResult): void
    {
        // For now, process immediately
        // In production, you might want to use Laravel Queue here
        $this->processAnalysis($analysisResult);
    }

    private function extractContentFromUrls(array $urls): array
    {
        return $this->webScrapingService->extractMultipleUrls($urls);
    }

    private function checkContentSuitability(array $extractionResults): array
    {
        $totalSuitabilityScore = 0;
        $totalUrls = 0;
        $allIssues = [];
        
        foreach ($extractionResults['results'] as $result) {
            if ($result['success']) {
                $suitability = $this->webScrapingService->isContentSuitable($result);
                $totalSuitabilityScore += $suitability['score'];
                $totalUrls++;
                $allIssues = array_merge($allIssues, $suitability['issues']);
            }
        }
        
        $averageScore = $totalUrls > 0 ? $totalSuitabilityScore / $totalUrls : 0;
        
        return [
            'suitable' => $averageScore >= 40, // Lower threshold for multiple URLs
            'average_score' => $averageScore,
            'issues' => array_unique($allIssues),
            'successful_extractions' => $totalUrls
        ];
    }

    private function performAIAnalysis(array $extractionResults, int $userId): array
    {
        $successfulExtractions = array_filter($extractionResults['results'], fn($r) => $r['success']);
        
        if (empty($successfulExtractions)) {
            throw new \Exception('No successful content extractions available for AI analysis');
        }
        
        return $this->groqService->analyzeUrls($successfulExtractions, $userId);
    }

    private function calculateProgress(AIAnalysisResult $analysisResult): array
    {
        $steps = [
            'pending' => ['percentage' => 0, 'step' => 'Menunggu proses', 'key' => 'pending'],
            'processing' => ['percentage' => 25, 'step' => 'Mengekstrak konten', 'key' => 'extraction'],
        ];
        
        if ($analysisResult->extracted_content) {
            $steps['processing'] = ['percentage' => 50, 'step' => 'Analisis AI sedang berlangsung', 'key' => 'analysis'];
        }
        
        if ($analysisResult->ai_resume) {
            $steps['processing'] = ['percentage' => 75, 'step' => 'Menyelesaikan analisis', 'key' => 'generation'];
        }
        
        $steps['completed'] = ['percentage' => 100, 'step' => 'Analisis selesai', 'key' => 'completed'];
        $steps['failed'] = ['percentage' => 0, 'step' => 'Analisis gagal', 'key' => 'failed'];
        
        $currentStatus = $analysisResult->processing_status;
        $current = $steps[$currentStatus] ?? $steps['pending'];
        
        $completedSteps = [];
        if ($analysisResult->extracted_content) $completedSteps[] = 'validation';
        if ($analysisResult->extracted_content) $completedSteps[] = 'extraction';
        if ($analysisResult->ai_resume) $completedSteps[] = 'analysis';
        if ($currentStatus === 'completed') $completedSteps[] = 'generation';
        
        // Estimate remaining time
        $timeRemaining = 'Menghitung...';
        if ($currentStatus === 'processing') {
            $elapsed = $analysisResult->created_at->diffInSeconds(now());
            $estimated = max(0, 120 - $elapsed); // Estimate 2 minutes total
            $timeRemaining = $estimated > 0 ? "{$estimated} detik" : '< 1 menit';
        } elseif ($currentStatus === 'completed') {
            $timeRemaining = '0 detik';
        }
        
        return [
            'percentage' => $current['percentage'],
            'current_step' => $current['step'],
            'step_key' => $current['key'],
            'completed_steps' => $completedSteps,
            'time_remaining' => $timeRemaining
        ];
    }

    private function prepareCombinedContent(array $extractedContent): array
    {
        $successfulExtractions = array_filter($extractedContent['results'] ?? [], fn($r) => $r['success']);
        
        $titles = [];
        $contents = [];
        
        foreach ($successfulExtractions as $content) {
            if (!empty($content['title'])) {
                $titles[] = $content['title'];
            }
            if (!empty($content['cleaned_content'])) {
                $contents[] = $content['cleaned_content'];
            }
        }
        
        return [
            'titles' => $titles,
            'full_content' => implode("\n\n---\n\n", $contents),
            'total_word_count' => str_word_count(implode(' ', $contents))
        ];
    }

    /**
     * Get analysis statistics for dashboard
     */
    public function getAnalysisStatistics(int $userId = null): array
    {
        $query = AIAnalysisResult::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $totalAnalyses = $query->count();
        $completedAnalyses = $query->where('processing_status', 'completed')->count();
        $failedAnalyses = $query->where('processing_status', 'failed')->count();
        
        $avgProcessingTime = $query->where('processing_status', 'completed')
            ->avg('processing_time');
        
        $todayAnalyses = $query->whereDate('created_at', today())->count();
        
        $recentAnalyses = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'total_analyses' => $totalAnalyses,
            'completed_analyses' => $completedAnalyses,
            'failed_analyses' => $failedAnalyses,
            'success_rate' => $totalAnalyses > 0 ? round(($completedAnalyses / $totalAnalyses) * 100, 2) : 0,
            'avg_processing_time' => round($avgProcessingTime ?? 0, 2),
            'today_analyses' => $todayAnalyses,
            'recent_analyses' => $recentAnalyses
        ];
    }

    /**
     * Clean up old analysis records
     */
    public function cleanupOldAnalyses(int $daysOld = 30): int
    {
        $deletedCount = AIAnalysisResult::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
        
        Log::info("Cleaned up {$deletedCount} old AI analysis records");
        
        return $deletedCount;
    }
}