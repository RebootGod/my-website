<?php

namespace App\Jobs;

use App\Models\UserActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Job: Process User Activity Analytics
 * 
 * Security: SQL injection protected via Eloquent
 * OWASP: Anomaly detection for fraud prevention
 * 
 * @package App\Jobs
 * @created 2025-10-09
 */
class ProcessUserActivityAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $startTime = microtime(true);

            // Aggregate user activity data
            $this->aggregateActivityData();

            // Calculate engagement scores
            $this->calculateEngagementScores();

            // Detect anomalies (potential security threats)
            $this->detectAnomalies();

            $duration = round((microtime(true) - $startTime), 2);

            Log::info('User activity analytics processed', [
                'duration_seconds' => $duration,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process user activity analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Aggregate user activity data
     *
     * @return void
     */
    protected function aggregateActivityData(): void
    {
        $last24h = now()->subDay();

        $activityStats = UserActivity::select('action', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $last24h)
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action');

        // Cache for 1 hour
        Cache::put('user_activity_stats_24h', $activityStats, 3600);

        Log::info('Activity data aggregated', [
            'total_actions' => $activityStats->sum(),
            'unique_actions' => $activityStats->count()
        ]);
    }

    /**
     * Calculate user engagement scores
     *
     * @return void
     */
    protected function calculateEngagementScores(): void
    {
        $last30Days = now()->subDays(30);

        $userEngagement = UserActivity::select(
                'user_id',
                DB::raw('COUNT(DISTINCT DATE(created_at)) as active_days'),
                DB::raw('COUNT(*) as total_actions')
            )
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $last30Days)
            ->groupBy('user_id')
            ->get()
            ->map(function ($item) {
                // Engagement score: (active_days * 10) + (total_actions / 10)
                $item->engagement_score = ($item->active_days * 10) + ($item->total_actions / 10);
                return $item;
            })
            ->sortByDesc('engagement_score')
            ->take(100); // Top 100 engaged users

        // Cache for 4 hours
        Cache::put('user_engagement_scores', $userEngagement, 14400);

        Log::info('Engagement scores calculated', [
            'total_users' => $userEngagement->count(),
            'top_score' => $userEngagement->first()?->engagement_score ?? 0
        ]);
    }

    /**
     * Detect anomalies (potential security threats)
     *
     * @return void
     */
    protected function detectAnomalies(): void
    {
        $last1Hour = now()->subHour();
        $anomalies = [];

        // Detect rapid repeated actions from same IP
        $suspiciousIPs = UserActivity::select('ip_address', DB::raw('COUNT(*) as action_count'))
            ->where('created_at', '>=', $last1Hour)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('action_count', '>', 100) // More than 100 actions per hour
            ->get();

        if ($suspiciousIPs->isNotEmpty()) {
            $anomalies['suspicious_ips'] = $suspiciousIPs->toArray();
            
            Log::channel('security')->warning('Suspicious IPs detected', [
                'ips' => $suspiciousIPs->pluck('ip_address'),
                'counts' => $suspiciousIPs->pluck('action_count', 'ip_address')
            ]);
        }

        // Detect failed login attempts
        $failedLogins = UserActivity::where('action', 'login_failed')
            ->where('created_at', '>=', $last1Hour)
            ->select('ip_address', DB::raw('COUNT(*) as failed_count'))
            ->groupBy('ip_address')
            ->having('failed_count', '>', 5)
            ->get();

        if ($failedLogins->isNotEmpty()) {
            $anomalies['failed_logins'] = $failedLogins->toArray();
            
            Log::channel('security')->warning('Multiple failed login attempts', [
                'ips' => $failedLogins->pluck('ip_address'),
                'counts' => $failedLogins->pluck('failed_count', 'ip_address')
            ]);
        }

        // Cache anomalies for 30 minutes
        if (!empty($anomalies)) {
            Cache::put('security_anomalies', $anomalies, 1800);
        }

        Log::info('Anomaly detection completed', [
            'suspicious_ips' => $suspiciousIPs->count(),
            'failed_login_sources' => $failedLogins->count()
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('User activity analytics job failed', [
            'error' => $exception->getMessage(),
            'timestamp' => now()
        ]);
    }
}
