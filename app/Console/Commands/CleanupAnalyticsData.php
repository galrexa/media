<?php
// ===================================================================
// app/Console/Commands/CleanupAnalyticsData.php
// Command untuk cleanup data lama

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAnalytics;
use App\Models\DailyAnalyticsSummary;
use App\Models\HourlyAnalyticsSummary;
use Carbon\Carbon;

class CleanupAnalyticsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:cleanup {--days=90 : Keep data for specified number of days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old analytics data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up analytics data older than {$days} days (before {$cutoffDate->format('Y-m-d')})...");
        
        if (!$this->confirm('This will permanently delete old analytics data. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        try {
            // Cleanup raw analytics data
            $deletedAnalytics = UserAnalytics::where('visited_at', '<', $cutoffDate)->delete();
            $this->info("✓ Deleted {$deletedAnalytics} analytics records");
            
            // Cleanup daily summaries
            $deletedDaily = DailyAnalyticsSummary::where('date', '<', $cutoffDate)->delete();
            $this->info("✓ Deleted {$deletedDaily} daily summary records");
            
            // Cleanup hourly summaries
            $deletedHourly = HourlyAnalyticsSummary::where('date', '<', $cutoffDate)->delete();
            $this->info("✓ Deleted {$deletedHourly} hourly summary records");
            
            $this->info('Analytics data cleanup completed!');
            
        } catch (\Exception $e) {
            $this->error('Failed to cleanup data: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}