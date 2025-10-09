<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessMovieAnalyticsJob;
use App\Jobs\CleanupExpiredInviteCodesJob;
use App\Jobs\ProcessUserActivityAnalyticsJob;
use App\Jobs\CacheWarmupJob;
use App\Jobs\ExportUserActivityReportJob;
use App\Jobs\BackupDatabaseJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// SCHEDULED JOBS
// ========================================

// Process Movie Analytics - Every 6 hours
Schedule::job(new ProcessMovieAnalyticsJob())
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('process-movie-analytics')
    ->description('Calculate trending movies and update view counts');

// Process User Activity Analytics - Every 4 hours
Schedule::job(new ProcessUserActivityAnalyticsJob())
    ->everyFourHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('process-user-activity-analytics')
    ->description('Aggregate user activity and detect anomalies');

// Cleanup Expired Invite Codes - Daily at 2:00 AM
Schedule::job(new CleanupExpiredInviteCodesJob())
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('cleanup-expired-invite-codes')
    ->description('Delete expired invite codes and notify admins');

// Cache Warmup - Every 2 hours
Schedule::job(new CacheWarmupJob())
    ->everyTwoHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('cache-warmup')
    ->description('Preload frequently accessed data into Redis cache');

// Export User Activity Report - Weekly (Every Monday at 8:00 AM)
Schedule::job(new ExportUserActivityReportJob('weekly'))
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('export-user-activity-report')
    ->description('Generate and email weekly user activity report to admins');

// Database Backup - Daily at 3:00 AM
Schedule::job(new BackupDatabaseJob('critical'))
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('database-backup')
    ->description('Backup critical database tables and notify admins');

// Additional scheduled tasks can be added here...
