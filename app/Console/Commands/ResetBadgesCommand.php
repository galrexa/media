<?php
// app/Console/Commands/ResetBadgesCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResetBadgesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'badges:reset {--user=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset badge hidden status for one or all users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('all')) {
            // Reset untuk semua user dengan pattern cache key
            $this->info('Resetting badges for all users...');

            // Clear semua cache key yang match pattern
            $keys = Cache::get('rejected_badge_hidden_*');
            foreach ($keys as $key) {
                Cache::forget($key);
            }

            // Reset session variable
            Session::forget('rejected_badge_hidden');

            $this->info('All badge statuses have been reset.');
            //Log::info('Reset badge status for all users via artisan command');

        } elseif ($userId = $this->option('user')) {
            // Reset untuk user tertentu
            $this->info("Resetting badges for user ID: {$userId}");

            $cacheKey = 'rejected_badge_hidden_' . $userId;
            Cache::forget($cacheKey);

            $this->info("Badge status for user {$userId} has been reset.");
            //Log::info("Reset badge status for user {$userId} via artisan command");

        } else {
            $this->error('Please specify --user=ID or --all to reset badges');
            return 1;
        }

        $this->info('Badge statuses have been reset. You may need to clear other caches:');
        $this->line('  php artisan cache:clear');
        $this->line('  php artisan view:clear');

        return 0;
    }
}
