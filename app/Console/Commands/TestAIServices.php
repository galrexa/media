<?php

// app/Console/Commands/TestAIServices.php
// Run: php artisan make:command TestAIServices

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAnalysisService;
use App\Services\WebScrapingService;
use App\Services\GroqAIService;

class TestAIServices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:test {--url=} {--full} {--provider=groq}';

    /**
     * The console command description.
     */
    protected $description = 'Test AI services functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing AI Services...');
        $this->newLine();

        // Test 1: Configuration Check
        $this->testConfiguration();

        // Test 2: Groq Connection
        $this->testGroqConnection();

        // Test 3: Web Scraping
        $this->testWebScraping();

        // Test 4: Full Analysis (if requested)
        if ($this->option('full')) {
            $this->testFullAnalysis();
        }

        $this->newLine();
        $this->info('✅ AI Services testing completed!');
    }

    private function testConfiguration()
    {
        $this->info('📋 Testing Configuration...');

        $checks = [
            'AI Enabled' => config('ai.enabled', false),
            'Groq API Key' => !empty(config('ai.groq_api_key')),
            'Groq Model' => config('ai.groq_model'),
            'Max URLs' => config('ai.max_urls_per_request'),
            'Timeout' => config('ai.timeout_seconds') . 's'
        ];

        foreach ($checks as $check => $value) {
            $status = $value ? '✅' : '❌';
            $this->line("  {$status} {$check}: " . ($value ?: 'Not configured'));
        }

        $this->newLine();
    }

    private function testGroqConnection()
    {
        $this->info('🤖 Testing Groq AI Connection...');

        try {
            $groqService = app(GroqAIService::class);
            $result = $groqService->testConnection();

            if ($result['success']) {
                $this->line("  ✅ Groq connection successful");
                $this->line("     Model: " . $result['model']);
                $this->line("     Response time: " . $result['response_time'] . 'ms');
                $this->line("     Test response: " . $result['test_response']);
            } else {
                $this->line("  ❌ Groq connection failed: " . $result['message']);
            }

        } catch (\Exception $e) {
            $this->line("  ❌ Groq test error: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testWebScraping()
    {
        $this->info('🕷️  Testing Web Scraping...');

        // Use working URLs for testing
        $testUrls = [
            'https://httpbin.org/html', // Simple test page
            'https://jsonplaceholder.typicode.com', // API test
            'https://www.google.com', // Basic connectivity test
        ];
        
        $testUrl = $this->option('url') ?: $testUrls[0];

        try {
            $webScrapingService = app(WebScrapingService::class);
            
            $this->line("  Testing URL: {$testUrl}");
            
            // Test URL validation
            $validation = $webScrapingService->validateUrl($testUrl);
            $status = $validation['accessible'] ? '✅ Accessible' : '❌ Not accessible';
            $this->line("  URL validation: {$status}");
            
            if (!$validation['accessible']) {
                $this->line("    Error: " . ($validation['error'] ?? 'Unknown error'));
                
                // Try backup URL
                $this->line("  Trying backup URL: https://httpbin.org/html");
                $backupValidation = $webScrapingService->validateUrl('https://httpbin.org/html');
                if ($backupValidation['accessible']) {
                    $testUrl = 'https://httpbin.org/html';
                    $this->line("  ✅ Backup URL accessible");
                }
            }
            
            // Test content preview if URL is accessible
            if ($validation['accessible'] || ($backupValidation['accessible'] ?? false)) {
                $preview = $webScrapingService->getUrlPreview($testUrl);
                if ($preview['success']) {
                    $this->line("  ✅ Content preview successful");
                    $this->line("     Title: " . ($preview['title'] ?: 'Not found'));
                    $this->line("     Domain: " . $preview['domain']);
                    $this->line("     Word count: " . $preview['word_count']);
                } else {
                    $this->line("  ❌ Preview failed: " . $preview['error']);
                }
                
                // Test full extraction (if requested)
                if ($this->option('full')) {
                    $extraction = $webScrapingService->extractContent($testUrl);
                    if ($extraction['success']) {
                        $this->line("  ✅ Full extraction successful");
                        $this->line("     Content length: " . strlen($extraction['cleaned_content']) . ' characters');
                        $this->line("     Language: " . ($extraction['language'] ?? 'unknown'));
                        
                        // Test content suitability
                        $suitability = $webScrapingService->isContentSuitable($extraction);
                        $this->line("     Suitability: " . $suitability['suitability'] . " (score: {$suitability['score']})");
                    } else {
                        $this->line("  ❌ Extraction failed: " . $extraction['error']);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->line("  ❌ Web scraping test error: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testFullAnalysis()
    {
        $this->info('🧠 Testing Full AI Analysis...');

        // Use simple test URLs that are likely to work
        $testUrls = [
            $this->option('url') ?: 'https://httpbin.org/html'
        ];

        try {
            $aiAnalysisService = app(AIAnalysisService::class);
            
            $this->line("  Starting full analysis with " . count($testUrls) . " URL(s)...");
            
            // Create a test user (use admin user or create dummy)
            $user = \App\Models\User::first();
            if (!$user) {
                $this->line("  ❌ No users found in database. Please create a user first.");
                return;
            }

            $sessionId = $aiAnalysisService->analyzeUrls($testUrls, $user->id);
            $this->line("  ✅ Analysis started with session ID: {$sessionId}");
            
            // Wait for completion (polling)
            $maxAttempts = 30; // 30 seconds max
            $attempt = 0;
            
            do {
                sleep(1);
                $status = $aiAnalysisService->getAnalysisStatus($sessionId);
                $attempt++;
                
                $this->line("     Progress: {$status['progress']}% - {$status['current_step']}");
                
                if ($status['status'] === 'completed') {
                    $this->line("  ✅ Analysis completed successfully!");
                    
                    $results = $aiAnalysisService->getAnalysisResults($sessionId);
                    if ($results) {
                        $this->line("     Resume length: " . strlen($results->ai_resume ?? '') . " characters");
                        $this->line("     Title suggestions: " . count($results->ai_judul_suggestions ?? []));
                        $this->line("     Tone: " . ($results->ai_tone_suggestion ?? 'unknown'));
                        $this->line("     Scale: " . ($results->ai_skala_suggestion ?? 'unknown'));
                        $this->line("     Processing time: " . ($results->processing_time ?? 0) . " seconds");
                        
                        // Show confidence scores
                        if ($results->confidence_scores) {
                            $avgConfidence = array_sum($results->confidence_scores) / count($results->confidence_scores);
                            $this->line("     Average confidence: " . round($avgConfidence, 1) . "%");
                        }
                    }
                    break;
                    
                } elseif ($status['status'] === 'failed') {
                    $this->line("  ❌ Analysis failed: " . ($status['error_message'] ?? 'Unknown error'));
                    break;
                }
                
            } while ($attempt < $maxAttempts);
            
            if ($attempt >= $maxAttempts) {
                $this->line("  ⏰ Analysis timeout after {$maxAttempts} seconds");
            }

        } catch (\Exception $e) {
            $this->line("  ❌ Full analysis test error: " . $e->getMessage());
        }
    }
}

// ===============================================================

// app/Console/Commands/CleanupAIData.php
// Run: php artisan make:command CleanupAIData

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAnalysisService;
use App\Models\AIAnalysisResult;
use App\Models\AIUsageLog;

class CleanupAIData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:cleanup {--days=30} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup old AI analysis data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("🧹 AI Data Cleanup" . ($dryRun ? ' (DRY RUN)' : ''));
        $this->line("Cleaning up data older than {$days} days...");
        $this->newLine();

        try {
            // Count records to be deleted
            $analysisCount = AIAnalysisResult::where('created_at', '<', now()->subDays($days))->count();
            $usageLogCount = AIUsageLog::where('created_at', '<', now()->subDays($days))->count();
            
            $this->info("Records to cleanup:");
            $this->line("  - AI Analysis Results: {$analysisCount}");
            $this->line("  - AI Usage Logs: {$usageLogCount}");
            $this->newLine();

            if ($analysisCount === 0 && $usageLogCount === 0) {
                $this->info("✅ No data to cleanup!");
                return;
            }

            if ($dryRun) {
                $this->info("🔍 DRY RUN - No data will be deleted");
                return;
            }

            if (!$this->confirm('Are you sure you want to delete this data?')) {
                $this->info("❌ Cleanup cancelled");
                return;
            }

            // Perform cleanup
            $aiAnalysisService = app(AIAnalysisService::class);
            $deletedCount = $aiAnalysisService->cleanupOldAnalyses($days);
            
            $deletedUsageLogs = AIUsageLog::where('created_at', '<', now()->subDays($days))->delete();

            $this->info("✅ Cleanup completed!");
            $this->line("  - Deleted {$deletedCount} analysis results");
            $this->line("  - Deleted {$deletedUsageLogs} usage logs");

        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: " . $e->getMessage());
        }
    }
}

// ===============================================================

// app/Console/Commands/AIStatistics.php
// Run: php artisan make:command AIStatistics

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAnalysisService;
use App\Models\AIUsageLog;
use App\Models\AIAnalysisResult;

class AIStatistics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:stats {--period=7}';

    /**
     * The console command description.
     */
    protected $description = 'Show AI usage statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = (int) $this->option('period');
        
        $this->info("📊 AI Usage Statistics (Last {$period} days)");
        $this->newLine();

        try {
            $aiAnalysisService = app(AIAnalysisService::class);
            $stats = $aiAnalysisService->getAnalysisStatistics();

            // Overall statistics
            $this->info("📈 Overall Statistics:");
            $this->line("  Total Analyses: " . $stats['total_analyses']);
            $this->line("  Completed: " . $stats['completed_analyses']);
            $this->line("  Failed: " . $stats['failed_analyses']);
            $this->line("  Success Rate: " . $stats['success_rate'] . "%");
            $this->line("  Avg Processing Time: " . $stats['avg_processing_time'] . "s");
            $this->line("  Today's Analyses: " . $stats['today_analyses']);
            $this->newLine();

            // Cost statistics
            $totalCostToday = AIUsageLog::getTotalCostToday();
            $totalCostMonth = AIUsageLog::getTotalCostThisMonth();
            $avgResponseTime = AIUsageLog::getAverageResponseTime();

            $this->info("💰 Cost & Performance:");
            $this->line("  Today's Cost: $" . number_format($totalCostToday, 4));
            $this->line("  This Month's Cost: $" . number_format($totalCostMonth, 4));
            $this->line("  Avg Response Time: " . round($avgResponseTime) . "ms");
            $this->line("  Success Rate Today: " . AIUsageLog::getSuccessRateToday() . "%");
            $this->newLine();

            // Provider statistics
            $providerStats = AIUsageLog::where('created_at', '>=', now()->subDays($period))
                ->selectRaw('ai_provider, COUNT(*) as count, AVG(response_time) as avg_time, SUM(cost_estimation) as total_cost')
                ->groupBy('ai_provider')
                ->get();

            $this->info("🤖 Provider Statistics:");
            foreach ($providerStats as $stat) {
                $this->line("  {$stat->ai_provider}:");
                $this->line("    Requests: {$stat->count}");
                $this->line("    Avg Response: " . round($stat->avg_time) . "ms");
                $this->line("    Total Cost: $" . number_format($stat->total_cost, 4));
            }
            $this->newLine();

            // Recent activity
            $this->info("🕒 Recent Activity:");
            foreach ($stats['recent_analyses'] as $analysis) {
                $status = $analysis->processing_status;
                $icon = $status === 'completed' ? '✅' : ($status === 'failed' ? '❌' : '⏳');
                $this->line("  {$icon} " . $analysis->created_at->format('Y-m-d H:i') . 
                          " - " . count($analysis->urls) . " URLs - " . 
                          ($analysis->user->name ?? 'Unknown'));
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to get statistics: " . $e->getMessage());
        }
    }
}