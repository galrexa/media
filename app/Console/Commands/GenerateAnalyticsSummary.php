<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyAnalyticsSummary;
use App\Models\HourlyAnalyticsSummary;
use Carbon\Carbon;

class GenerateAnalyticsSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:generate-summary {--date= : Generate summary for specific date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily and hourly analytics summary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();
        
        $this->info("Generating analytics summary for {$date->format('Y-m-d')}...");
        
        try {
            // Generate daily summary
            DailyAnalyticsSummary::generateDailySummary($date);
            $this->info('✓ Daily summary generated');
            
            // Generate hourly summary
            HourlyAnalyticsSummary::generateHourlySummary($date);
            $this->info('✓ Hourly summary generated');
            
            $this->info('Analytics summary generation completed!');
            
        } catch (\Exception $e) {
            $this->error('Failed to generate summary: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}