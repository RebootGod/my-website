<?php

namespace App\Console\Commands;

use App\Services\UserActivityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command: Cleanup Old User Activities
 * 
 * Automatically deletes user activity records older than 14 days
 * Runs daily via Laravel scheduler
 * 
 * @package App\Console\Commands
 */
class CleanupOldUserActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:cleanup {--days=14 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup user activity records older than specified days (default: 14 days)';

    /**
     * User activity service
     */
    protected UserActivityService $activityService;

    /**
     * Create a new command instance.
     */
    public function __construct(UserActivityService $activityService)
    {
        parent::__construct();
        $this->activityService = $activityService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("🗑️  Starting cleanup of user activities older than {$days} days...");

        try {
            $deletedCount = $this->activityService->cleanupOldActivities($days);

            $this->info("✅ Successfully cleaned up {$deletedCount} old activity records");

            Log::info('Scheduled user activity cleanup completed', [
                'deleted_count' => $deletedCount,
                'older_than_days' => $days,
                'executed_at' => now()->toDateTimeString()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: {$e->getMessage()}");

            Log::error('Scheduled user activity cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'executed_at' => now()->toDateTimeString()
            ]);

            return Command::FAILURE;
        }
    }
}
